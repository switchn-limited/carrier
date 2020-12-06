<?php

namespace App\Http\Livewire;

use App\Services\LibPhoneNumberService;
use libphonenumber\NumberParseException;
use Livewire\Component;

class CarrierFinder extends Component
{
    public $phone;
    public $carrier;
    public $message;

    public function find() {
        try {
            $libPhoneNumberService = new LibPhoneNumberService();

            $this->carrier = $libPhoneNumberService->getCarrierForNumber($this->phone);
            $this->message = null;

        } catch (NumberParseException $e) {

            $this->carrier = null;
            $this->message = $e->getMessage();

        }
    }

    public function render()
    {
        if ($this->phone) {
            $this->find();
        }

        return view('livewire.carrier-finder');
    }
}
