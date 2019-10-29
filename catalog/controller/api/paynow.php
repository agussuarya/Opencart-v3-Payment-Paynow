<?php
class ControllerApiPaynow extends Controller
{
    public function staging()
    {
        $json = [
            'a' => 'b'
        ];
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function prod()
    {
        $json = [
            'a' => 'a'
        ];
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}