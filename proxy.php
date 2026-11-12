<?php
/*
 * SEO Check – CORS proxy
 * Auf eigenem PHP-Hosting ablegen (z.B. https://lake-studio.de/tools/proxy.php).
 * Dann im SEO-Tool: const CUSTOM_PROXY = "https://lake-studio.de/tools/proxy.php?url=";
 */

// Allow the tool (served from anywhere) to read the response
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: text/html; charset=utf-8' );

$url = isset( $_GET['url'] ) ? trim( $_GET['url'] ) : '';

// Basic validation
if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
	http_response_code( 400 );
	exit( 'Invalid url' );
}

$scheme = strtolower( parse_url( $url, PHP_URL_SCHEME ) );

if ( $scheme !== 'http' && $scheme !== 'https' ) {
	http_response_code( 400 );
	exit( 'Only http/https allowed' );
}

// Block private / loopback targets (SSRF protection)
$host = parse_url( $url, PHP_URL_HOST );
$ip = gethostbyname( $host );

if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) &&
	! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
	http_response_code( 403 );
	exit( 'Blocked target' );
}

// Fetch via cURL (follows redirects, sets a browser UA)
$ch = curl_init( $url );

curl_setopt_array( $ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_MAXREDIRS => 5,
	CURLOPT_TIMEOUT => 15,
	CURLOPT_CONNECTTIMEOUT => 8,
	CURLOPT_SSL_VERIFYPEER => true,
	CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LS-SEO-Check/1.0)',
	CURLOPT_ENCODING => '' // accept gzip/deflate
]);

$body = curl_exec( $ch );
$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
$err = curl_error( $ch );

curl_close( $ch );

if ( $body === false ) {
	http_response_code( 502 );
	exit( 'Fetch failed: ' . $err );
}

http_response_code( $code ?: 200 );

echo $body;
