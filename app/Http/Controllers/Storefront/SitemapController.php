<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            route('cafe.home', absolute: true),
            route('cafe.menu', absolute: true),
        ];

        $body = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $loc) {
            $body .= '<url><loc>'.e($loc).'</loc></url>';
        }
        $body .= '</urlset>';

        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
