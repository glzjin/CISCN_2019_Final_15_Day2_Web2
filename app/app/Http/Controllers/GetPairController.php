<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Elliptic\EC;

class GetPairController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }


    public function getPair (Request $request) {
        $ec = new EC('secp256k1');
        $alice = $ec->genKeyPair();
        $alicePublic = $alice->getPublic(true, 'hex');
        $request->session()->put('private', $alice->getPrivate()->toString('hex'));
        return response($alicePublic);
    }
}
