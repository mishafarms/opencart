<?php

class ControllerAmazonListingReport extends Controller {

    public function index() {
        if ($this->config->get('amazon_status') != '1') {
            return;
        }
        
        $this->load->model('amazon/product');
        
        $logger = new Log('amazon.log');
        $logger->write('amazon/listingReports - started');
        
        $token = $this->config->get('openbay_amazon_token');
        
        $incomingToken = isset($this->request->post['token']) ? $this->request->post['token'] : '';
        
        if ($incomingToken !== $token) {
            $logger->write('amazon/listingReports - Incorrect token: ' . $incomingToken);
            return;
        }
        
        $decrypted = $this->openbay->amazon->decryptArgs($this->request->post['data']);
        
        if (!$decrypted) {
            $logger->write('amazon/listingReports - Failed to decrypt data');
            return;
        }
        
        $logger->write('Received Listing Report: ' . $decrypted);
        
        $request = json_decode($decrypted, 1);
        
        $data = array();
        
        foreach ($request['products'] as $product) {
            $data[] = array(
                'marketplace' => $request['marketplace'],
                'sku' => $product['sku'],
                'quantity' => $product['quantity'],
                'asin' => $product['asin'],
                'price' => $product['price'],
            );
        }
        
        if ($data) {
            $this->model_amazon_product->addListingReport($data);
        }
        
        $this->model_amazon_product->removeListingReportLock($request['marketplace']);
        
        $logger->write(print_r($this->config->get('openbay_amazon_processing_listing_reports'), 1));
        $logger->write('amazon/listingReports - Finished');
    }

}