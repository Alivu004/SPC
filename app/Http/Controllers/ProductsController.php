<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        // TODO: replace with real repository queries when ready
        return $this->render('Products', [
            'products' => [
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 0,
                ],
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
            ],
            'sort' => $request->sort ?? 'score_desc',
        ]);
    }
}
