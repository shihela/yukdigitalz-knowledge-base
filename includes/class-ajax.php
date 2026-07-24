<?php
namespace YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all AJAX requests securely (Live Search, Voting, AI Chat).
 */
class Ajax {
	/**
	 * Hooks AJAX endpoints.
	 */
	public function init() {
		// AJAX Live Search
		add_action( 'wp_ajax_yukdigitalz_kb_search', array( $this, 'handle_search' ) );
		add_action( 'wp_ajax_nopriv_yukdigitalz_kb_search', array( $this, 'handle_search' ) );

		// AJAX Voting
		add_action( 'wp_ajax_yukdigitalz_kb_vote', array( $this, 'handle_vote' ) );
		add_action( 'wp_ajax_nopriv_yukdigitalz_kb_vote', array( $this, 'handle_vote' ) );

		// AJAX AI Chat
		add_action( 'wp_ajax_yukdigitalz_kb_ai_chat', array( $this, 'handle_ai_chat' ) );
		add_action( 'wp_ajax_nopriv_yukdigitalz_kb_ai_chat', array( $this, 'handle_ai_chat' ) );

		// Clear Search Logs Action (Admin Only)
		add_action( 'wp_ajax_yukdigitalz_kb_clear_search_logs', array( $this, 'handle_clear_search_logs' ) );
	}

	/**
	 * Performs secure, sanitized post searching.
	 */
	public function handle_search() {
		// Nonce check to verify source request
		check_ajax_referer( 'yukdigitalz_kb_ajax_nonce', 'security' );

		$query_str = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

		if ( empty( $query_str ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Search term is empty', 'yukdigitalz-knowledge-base' ) ) );
		}

		$args = array(
			'post_type'      => 'yukdigitalz_kb_doc',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			's'              => $query_str,
		);

		$search_query = new \WP_Query( $args );
		$results = array();

		if ( $search_query->have_posts() ) {
			while ( $search_query->have_posts() ) {
				$search_query->the_post();
				$terms    = get_the_terms( get_the_ID(), 'yukdigitalz_kb_cat' );
				$cat_name = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';

				$results[] = array(
					'id'        => get_the_ID(),
					'title'     => esc_html( get_the_title() ),
					'permalink' => esc_url( get_permalink() ),
					'excerpt'   => esc_html( wp_trim_words( get_the_excerpt(), 15 ) ),
					'category'  => esc_html( $cat_name ),
				);
			}
			wp_reset_postdata();
		}

		// Expose filter for PRO addon to modify search results (e.g. inject vector search results)
		$results = apply_filters( 'yukdigitalz_kb_search_results', $results, $query_str );

		// Log search query analytics
		$this->log_search_query( $query_str, count( $results ) );

		if ( ! empty( $results ) ) {
			wp_send_json_success( $results );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'No articles found matching this query.', 'yukdigitalz-knowledge-base' ) ) );
		}
	}

	/**
	 * Increments helpful or not-helpful feedback counters securely.
	 */
	public function handle_vote() {
		// Nonce check
		check_ajax_referer( 'yukdigitalz_kb_ajax_nonce', 'security' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$vote    = isset( $_POST['vote'] ) ? sanitize_key( $_POST['vote'] ) : '';

		if ( ! $post_id || ! in_array( $vote, array( 'helpful', 'not_helpful' ), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid parameters', 'yukdigitalz-knowledge-base' ) ) );
		}

		// Check cookie to prevent multiple votes from same browser session
		$cookie_name = 'yukdigitalz_kb_voted_' . $post_id;
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You have already voted on this article.', 'yukdigitalz-knowledge-base' ) ) );
		}

		$meta_key = ( $vote === 'helpful' ) ? '_yukdigitalz_kb_helpful_votes' : '_yukdigitalz_kb_not_helpful_votes';
		$current_votes = (int) get_post_meta( $post_id, $meta_key, true );
		$new_votes = $current_votes + 1;
		update_post_meta( $post_id, $meta_key, $new_votes );

		// Set cookie for 30 days securely
		setcookie( $cookie_name, '1', time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

		wp_send_json_success( array(
			'message'     => esc_html__( 'Thank you for your feedback!', 'yukdigitalz-knowledge-base' ),
			'helpful'     => (int) get_post_meta( $post_id, '_yukdigitalz_kb_helpful_votes', true ),
			'not_helpful' => (int) get_post_meta( $post_id, '_yukdigitalz_kb_not_helpful_votes', true ),
		) );
	}

	/**
	 * Handles AJAX AI Chat request with RAG context retrieval and Gemini LLM.
	 */
	public function handle_ai_chat() {
		// Nonce check for security validation
		check_ajax_referer( 'yukdigitalz_kb_ajax_nonce', 'security' );

		$message          = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- History JSON is decoded and each item is manually sanitized key-by-key below.
		$chat_history_raw = isset( $_POST['history'] ) ? json_decode( wp_unslash( $_POST['history'] ), true ) : array();
		$chat_history     = array();
		if ( is_array( $chat_history_raw ) ) {
			foreach ( $chat_history_raw as $msg ) {
				if ( is_array( $msg ) ) {
					$chat_history[] = array(
						'role'    => isset( $msg['role'] ) ? sanitize_key( $msg['role'] ) : 'user',
						'content' => isset( $msg['content'] ) ? sanitize_text_field( $msg['content'] ) : '',
					);
				}
			}
		}

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Message is empty.', 'yukdigitalz-knowledge-base' ) ) );
		}

		// Security Rate-Limiting Check (Hashed IP in Transients)
		$enable_rate_limit = (int) get_option( 'yukdigitalz_kb_enable_rate_limit', 1 );
		if ( $enable_rate_limit ) {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$hashed_ip = md5( $ip . 'ykb_salt_security_123' );
			$limit_key = 'ykb_limit_' . $hashed_ip;
			
			$max_queries = (int) get_option( 'yukdigitalz_kb_rate_limit_count', 20 );
			$limit_data = get_transient( $limit_key );

			if ( ! is_array( $limit_data ) ) {
				$limit_data = array(
					'count'   => 1,
					'expires' => time() + HOUR_IN_SECONDS,
				);
				set_transient( $limit_key, $limit_data, HOUR_IN_SECONDS );
			} else {
				$remaining = $limit_data['expires'] - time();
				if ( $remaining <= 0 ) {
					// Expired or resetting timer
					$limit_data = array(
						'count'   => 1,
						'expires' => time() + HOUR_IN_SECONDS,
					);
					set_transient( $limit_key, $limit_data, HOUR_IN_SECONDS );
				} else {
					$limit_data['count']++;
					if ( $limit_data['count'] > $max_queries ) {
						wp_send_json_error( array( 'message' => esc_html__( 'Batas kuota chat tercapai. Anda telah melebihi batas maksimum pertanyaan per jam. Silakan coba lagi nanti.', 'yukdigitalz-knowledge-base' ) ) );
					}
					set_transient( $limit_key, $limit_data, $remaining );
				}
			}
		}

		// RAG Step: Search documentation for relevant post records (retrieve top 3 results)
		$args = array(
			'post_type'      => 'yukdigitalz_kb_doc',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			's'              => $message,
		);
		$search_query = new \WP_Query( $args );
		$context      = '';

		if ( $search_query->have_posts() ) {
			$context .= "Berikut adalah beberapa artikel dokumentasi yang relevan dari basis pengetahuan kami:\n\n";
			while ( $search_query->have_posts() ) {
				$search_query->the_post();
				$context .= "Judul: " . get_the_title() . "\n";
				$context .= "Isi:\n" . wp_strip_all_tags( wp_trim_words( get_the_content(), 250 ) ) . "\n\n";
			}
		}

		// Expose filter for PRO addon to modify/augment chat context (e.g. inject vector search context)
		$context = apply_filters( 'yukdigitalz_kb_ai_chat_context', $context, $message, $chat_history );

		// Compile the System prompt instructions
		$bot_name      = __( 'Asisten AI Yukdigitalz KB', 'yukdigitalz-knowledge-base' );
		$site_name     = get_bloginfo( 'name' );
		$system_prompt = "Anda adalah asisten AI ramah dan profesional bernama '{$bot_name}' untuk situs '{$site_name}'.\n";
		$system_prompt .= "Tugas utama Anda adalah menjawab pertanyaan pengunjung menggunakan dokumentasi/knowledge base yang disediakan di bawah ini.\n";
		$system_prompt .= "Pedoman Ketat:\n";
		$system_prompt .= "1. Jawablah pertanyaan pengunjung HANYA berdasarkan dokumentasi yang diberikan di bawah. Jangan membuat asumsi di luar konteks.\n";
		$system_prompt .= "2. Jika jawaban tidak ada dalam dokumentasi, katakan secara jujur bahwa Anda tidak tahu atau tidak dapat menemukannya di dokumentasi kami.\n";
		$system_prompt .= "3. Jawablah menggunakan bahasa yang sama dengan yang digunakan pengunjung.\n";
		$system_prompt .= "4. Jaga agar jawaban tetap ringkas, terstruktur, dan jelas. Gunakan bullet points atau daftar jika membantu penjelasan.\n\n";

		if ( ! empty( $context ) ) {
			$system_prompt .= "Konteks Dokumentasi:\n{$context}\n";
		}

		$ai_response = '';

		// Scenario A: Native WordPress AI Client support (WP 7.0+ / custom helper)
		if ( function_exists( 'wp_ai_client_prompt' ) ) {
			$wp_history = array();
			if ( is_array( $chat_history ) ) {
				foreach ( $chat_history as $msg ) {
					$role    = isset( $msg['role'] ) ? $msg['role'] : 'user';
					$content = isset( $msg['content'] ) ? $msg['content'] : '';

					if ( 'user' === $role ) {
						if ( class_exists( 'WordPress\AiClient\Messages\DTO\UserMessage' ) && class_exists( 'WordPress\AiClient\Messages\DTO\MessagePart' ) ) {
							$wp_history[] = new \WordPress\AiClient\Messages\DTO\UserMessage( array( new \WordPress\AiClient\Messages\DTO\MessagePart( $content ) ) );
						}
					} elseif ( 'assistant' === $role || 'model' === $role ) {
						if ( class_exists( 'WordPress\AiClient\Messages\DTO\ModelMessage' ) && class_exists( 'WordPress\AiClient\Messages\DTO\MessagePart' ) ) {
							$wp_history[] = new \WordPress\AiClient\Messages\DTO\ModelMessage( array( new \WordPress\AiClient\Messages\DTO\MessagePart( $content ) ) );
						}
					}
				}
			}

			try {
				$builder = call_user_func( 'wp_ai_client_prompt', $message )
					->using_system_instruction( $system_prompt )
					->using_temperature( 0.7 );

				if ( ! empty( $wp_history ) ) {
					$builder->with_history( ...$wp_history );
				}

				if ( $builder->is_supported_for_text_generation() ) {
					$result = $builder->generate_text();
					if ( ! is_wp_error( $result ) ) {
						$ai_response = $result;
					}
				}
			} catch ( \Exception $e ) {
				// Suppress client instantiation crashes and allow fallback execution
			}
		}

		// Scenario B: Fallback to direct Google Gemini API call
		if ( empty( $ai_response ) ) {
			$api_key = get_option( 'yukdigitalz_kb_gemini_api_key' );

			// Check backup option from Gitaz Command Center plugin
			if ( empty( $api_key ) ) {
				$api_key = get_option( 'gitaz_ai_gemini_key' );
			}

			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Gemini API Key is not configured. Please configure it in your Settings.', 'yukdigitalz-knowledge-base' ) ) );
			}

			$api_url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key=' . $api_key;
			$contents = array();

			// Format conversation history for Gemini API DTO structure
			if ( is_array( $chat_history ) ) {
				foreach ( $chat_history as $msg ) {
					$role       = ( isset( $msg['role'] ) && 'user' === $msg['role'] ) ? 'user' : 'model';
					$contents[] = array(
						'role'  => $role,
						'parts' => array(
							array( 'text' => $msg['content'] )
						)
					);
				}
			}

			// Add current user prompt
			$contents[] = array(
				'role'  => 'user',
				'parts' => array(
					array( 'text' => $message )
				)
			);

			$payload = array(
				'contents'          => $contents,
				'systemInstruction' => array(
					'parts' => array(
						array( 'text' => $system_prompt )
					)
				),
				'generationConfig'  => array(
					'temperature' => 0.5,
				)
			);

			$response = wp_remote_post( $api_url, array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 15,
			) );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
				$ai_response = $data['candidates'][0]['content']['parts'][0]['text'];
			} else {
				$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Failed to communicate with Gemini API.', 'yukdigitalz-knowledge-base' );
				wp_send_json_error( array( 'message' => $error_msg ) );
			}
		}

		wp_send_json_success( array( 'response' => $ai_response ) );
	}

	/**
	 * Logs search queries and their results count in a database option.
	 *
	 * @param string $term Query string.
	 * @param int    $results_count Results count.
	 */
	private function log_search_query( $term, $results_count ) {
		$term = sanitize_text_field( $term );
		$term = trim( $term );
		if ( empty( $term ) ) {
			return;
		}
		
		// Convert to lowercase safely (multibyte)
		if ( function_exists( 'mb_strtolower' ) ) {
			$term = mb_strtolower( $term );
		} else {
			$term = strtolower( $term );
		}

		$logs = get_option( 'yukdigitalz_kb_search_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		if ( isset( $logs[ $term ] ) ) {
			$logs[ $term ]['hits']++;
			$logs[ $term ]['results_count'] = $results_count;
			$logs[ $term ]['last_searched'] = time();
		} else {
			$logs[ $term ] = array(
				'hits'          => 1,
				'results_count' => $results_count,
				'last_searched' => time(),
			);
		}

		// Sort by hits DESC, then by last_searched DESC
		uasort( $logs, function( $a, $b ) {
			if ( $a['hits'] === $b['hits'] ) {
				return $b['last_searched'] - $a['last_searched'];
			}
			return $b['hits'] - $a['hits'];
		} );

		// Cap size to 100 entries to prevent option database bloat
		if ( count( $logs ) > 100 ) {
			$logs = array_slice( $logs, 0, 100, true );
		}

		update_option( 'yukdigitalz_kb_search_logs', $logs, false );
	}

	/**
	 * Deletes all search analytics logs.
	 */
	public function handle_clear_search_logs() {
		check_ajax_referer( 'yukdigitalz_kb_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized access', 'yukdigitalz-knowledge-base' ) ) );
		}

		delete_option( 'yukdigitalz_kb_search_logs' );
		wp_send_json_success( array( 'message' => esc_html__( 'Search logs cleared successfully.', 'yukdigitalz-knowledge-base' ) ) );
	}
}
