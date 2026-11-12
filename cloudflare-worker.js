/*
 * SEO Check – CORS proxy als Cloudflare Worker (kostenlos, global, immer erreichbar).
 *
 * Setup:
 *   1. dash.cloudflare.com → Workers & Pages → Create → Worker
 *   2. Diesen Code einfügen, Deploy
 *   3. URL z.B. https://seo-proxy.DEIN-SUBDOMAIN.workers.dev
 *   4. Im SEO-Tool: const CUSTOM_PROXY = "https://seo-proxy.DEIN-SUBDOMAIN.workers.dev/?url=";
 */

export default {

	async fetch( request ) {

		const { searchParams } = new URL( request.url );
		const target = searchParams.get( "url" );

		if ( ! target ) {
			return new Response( "Missing url", { status: 400 } );
		}

		let u;

		try {
			u = new URL( target );
		} catch ( e ) {
			return new Response( "Invalid url", { status: 400 } );
		}

		if ( u.protocol !== "http:" && u.protocol !== "https:" ) {
			return new Response( "Only http/https", { status: 400 } );
		}

		try {

			const res = await fetch( u.toString(), {
				redirect: "follow",
				headers: { "User-Agent": "Mozilla/5.0 (compatible; LS-SEO-Check/1.0)" }
			});

			const body = await res.text();

			return new Response( body, {
				status: res.status,
				headers: {
					"Content-Type": "text/html; charset=utf-8",
					"Access-Control-Allow-Origin": "*"
				}
			});

		} catch ( e ) {
			return new Response( "Fetch failed: " + e.message, { status: 502 } );
		}

	}

};
