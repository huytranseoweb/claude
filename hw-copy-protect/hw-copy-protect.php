<?php
/**
 * Plugin Name: HW Copy Protect
 * Description: Chặn copy text, ảnh, chuột phải, bôi đen, phím tắt với khách truy cập. Admin dùng bình thường. Không ảnh hưởng Googlebot / AI crawler.
 * Version:     1.0.0
 * Author:      Huy Tran
 * License:     GPL-2.0-or-later
 * Text Domain: hw-copy-protect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HWCP_VERSION', '1.0.0' );
define( 'HWCP_URL', plugin_dir_url( __FILE__ ) );
define( 'HWCP_OPTION', 'hwcp_settings' );

/**
 * Cấu hình mặc định.
 */
function hwcp_defaults() {
	return array(
		'enabled'       => 1,
		'right_click'   => 1,
		'select'        => 1,
		'copy'          => 1,
		'image_drag'    => 1,
		'shortcuts'     => 1, // Ctrl+C/X/A/S/U/P, F12, Ctrl+Shift+I/J/C.
		'show_notice'   => 1,
		'notice_text'   => 'Nội dung được bảo vệ bản quyền.',
		'bypass_cap'    => 'manage_options', // Quyền được miễn chặn.
		'extra_bots'    => '',
	);
}

function hwcp_settings() {
	return wp_parse_args( get_option( HWCP_OPTION, array() ), hwcp_defaults() );
}

/**
 * Nhận diện bot tìm kiếm & AI crawler theo User-Agent.
 * Lưu ý: HTML trả về cho bot và người dùng là GIỐNG HỆT nhau, chỉ khác việc
 * không nạp file JS/CSS chặn copy => không phải cloaking.
 */
function hwcp_is_bot() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( '' === $ua ) {
		return true;
	}

	$bots = array(
		// Search engines.
		'googlebot', 'google-inspectiontool', 'googleother', 'google-extended', 'adsbot-google',
		'mediapartners-google', 'apis-google', 'storebot-google', 'bingbot', 'msnbot', 'bingpreview',
		'yandex', 'baiduspider', 'duckduckbot', 'slurp', 'applebot', 'coccocbot', 'seznambot', 'petalbot',
		// AI crawlers.
		'gptbot', 'chatgpt-user', 'oai-searchbot', 'claudebot', 'claude-user', 'claude-searchbot',
		'anthropic-ai', 'perplexitybot', 'perplexity-user', 'ccbot', 'bytespider', 'amazonbot',
		'meta-externalagent', 'facebookbot', 'cohere-ai', 'mistralai-user', 'youbot', 'diffbot',
		// Social / preview.
		'facebookexternalhit', 'twitterbot', 'linkedinbot', 'zalo', 'telegrambot', 'whatsapp', 'slackbot',
		'discordbot', 'pinterest',
		// SEO tools.
		'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'screaming frog', 'lighthouse', 'chrome-lighthouse',
		'pagespeed', 'gtmetrix',
	);

	$extra = hwcp_settings()['extra_bots'];
	if ( $extra ) {
		$bots = array_merge( $bots, array_filter( array_map( 'trim', explode( "\n", strtolower( $extra ) ) ) ) );
	}

	foreach ( $bots as $bot ) {
		if ( false !== strpos( $ua, $bot ) ) {
			return true;
		}
	}
	return (bool) preg_match( '/bot|crawl|spider|slurp|fetch|scrape|headless/i', $ua );
}

/**
 * Có áp dụng chặn cho request hiện tại không.
 */
function hwcp_should_protect() {
	$s = hwcp_settings();
	if ( empty( $s['enabled'] ) || is_admin() || is_feed() || is_customize_preview() || wp_doing_ajax() ) {
		return false;
	}
	if ( is_user_logged_in() && current_user_can( $s['bypass_cap'] ) ) {
		return false;
	}
	if ( hwcp_is_bot() ) {
		return false;
	}
	return (bool) apply_filters( 'hwcp_should_protect', true );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! hwcp_should_protect() ) {
		return;
	}
	$s = hwcp_settings();

	wp_enqueue_style( 'hwcp', HWCP_URL . 'assets/protect.css', array(), HWCP_VERSION );
	wp_enqueue_script( 'hwcp', HWCP_URL . 'assets/protect.js', array(), HWCP_VERSION, true );
	wp_localize_script( 'hwcp', 'HWCP', array(
		'rightClick' => (int) $s['right_click'],
		'select'     => (int) $s['select'],
		'copy'       => (int) $s['copy'],
		'imageDrag'  => (int) $s['image_drag'],
		'shortcuts'  => (int) $s['shortcuts'],
		'notice'     => $s['show_notice'] ? $s['notice_text'] : '',
	) );
} );

/* ---------------- Trang cài đặt ---------------- */

add_action( 'admin_menu', function () {
	add_options_page( 'HW Copy Protect', 'Copy Protect', 'manage_options', 'hwcp', 'hwcp_render_settings' );
} );

add_action( 'admin_init', function () {
	register_setting( 'hwcp', HWCP_OPTION, array( 'sanitize_callback' => 'hwcp_sanitize' ) );
} );

function hwcp_sanitize( $in ) {
	$out = array();
	foreach ( array( 'enabled', 'right_click', 'select', 'copy', 'image_drag', 'shortcuts', 'show_notice' ) as $k ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	$out['notice_text'] = sanitize_text_field( $in['notice_text'] ?? '' );
	$out['bypass_cap']  = in_array( $in['bypass_cap'] ?? '', array( 'manage_options', 'edit_others_posts', 'edit_posts', 'read' ), true )
		? $in['bypass_cap'] : 'manage_options';
	$out['extra_bots']  = sanitize_textarea_field( $in['extra_bots'] ?? '' );
	return $out;
}

function hwcp_render_settings() {
	$s      = hwcp_settings();
	$name   = HWCP_OPTION;
	$checks = array(
		'enabled'     => 'Bật bảo vệ',
		'right_click' => 'Chặn chuột phải',
		'select'      => 'Chặn bôi đen (select)',
		'copy'        => 'Chặn copy / cut',
		'image_drag'  => 'Chặn kéo-thả & lưu ảnh',
		'shortcuts'   => 'Chặn phím tắt (Ctrl+C/A/S/U/P, F12, DevTools)',
		'show_notice' => 'Hiện thông báo khi bị chặn',
	);
	$caps   = array(
		'manage_options'    => 'Chỉ Administrator',
		'edit_others_posts' => 'Editor trở lên',
		'edit_posts'        => 'Author/Contributor trở lên',
		'read'              => 'Mọi user đã đăng nhập',
	);
	?>
	<div class="wrap">
		<h1>HW Copy Protect</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'hwcp' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $checks as $k => $label ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td><input type="checkbox" name="<?php echo esc_attr( "{$name}[{$k}]" ); ?>" value="1" <?php checked( $s[ $k ], 1 ); ?>></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row">Nội dung thông báo</th>
					<td><input type="text" class="regular-text" name="<?php echo esc_attr( "{$name}[notice_text]" ); ?>" value="<?php echo esc_attr( $s['notice_text'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row">Ai được copy bình thường</th>
					<td>
						<select name="<?php echo esc_attr( "{$name}[bypass_cap]" ); ?>">
							<?php foreach ( $caps as $cap => $label ) : ?>
								<option value="<?php echo esc_attr( $cap ); ?>" <?php selected( $s['bypass_cap'], $cap ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Bot bổ sung (mỗi dòng 1 chuỗi UA)</th>
					<td><textarea name="<?php echo esc_attr( "{$name}[extra_bots]" ); ?>" rows="4" class="large-text code"><?php echo esc_textarea( $s['extra_bots'] ); ?></textarea>
					<p class="description">Đã có sẵn Googlebot, Bingbot, Cốc Cốc, GPTBot, ClaudeBot, PerplexityBot, CCBot, Zalo, Facebook...</p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=hwcp' ) ) . '">Cài đặt</a>' );
	return $links;
} );
