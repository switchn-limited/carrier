<?php

namespace App\Http\Controllers;


use App\Services\LibPhoneNumberService;
use Illuminate\Http\Request;
use libphonenumber\NumberParseException;

class PhoneNumberCarrierController extends Controller
{
    public function __construct()
    {
        //
    }

    public function __invoke($phone)
    {
        try {
            $libPhoneNumberService = new LibPhoneNumberService();
            return [
                'carrier' => $libPhoneNumberService->getCarrierForNumber($phone)
            ];
        } catch (NumberParseException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
