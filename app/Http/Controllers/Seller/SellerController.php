<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\Request;

abstract class SellerController extends Controller
{
    /**
     * Every seller screen is scoped to the signed-in user's store. Resolving it
     * in one place means no controller can forget the ownership check.
     */
    protected function seller(Request $request): Seller
    {
        $seller = $request->user()?->seller;

        abort_if($seller === null, 403, 'لا يوجد متجر مرتبط بهذا الحساب.');

        return $seller;
    }
}
