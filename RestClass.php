<?php
class RestClass
{
    private $method = "GET";
    private $headers = [];
    private $query = [];
    private $body = "";
    private $base_url = "";

    public function __construct($baseURL){
        $this->base_url = $baseURL;
    }

    public function add_header($key, $value){
        if(!isset($key) || !isset($value)) return;

        array_push($this->headers, "{$key}: {$value}");
    }

    public function add_string_body($string_body){
        $this->body = $string_body;
    }

    public function add_array_body($array_body){
        $this->body = json_encode($array_body);
    }

    public function rest_request($path = "", $method = "GET"){
        try {                
            $ch = curl_init();

            // Correcting the URL construction
            if (!empty($path) && substr($path, 0, 1) === '/') {
                $path = ltrim($path, '/');
            }
            $url = $this->base_url . '/' . $path;

            if (!empty($this->query)) {
                $query_string = http_build_query($this->query);
                $url .= '?' . $query_string;
            }

            curl_setopt($ch, CURLOPT_URL, $url);

            // Handling request method and body
            $this->method = strtoupper($method);
            switch ($this->method) {
                case "POST":
                    curl_setopt($ch, CURLOPT_POST, true);
                    if ($this->body) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
                    }
                    break;
                case "PUT":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    if ($this->body) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
                    }
                    break;
                case "DELETE":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    if ($this->body) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
                    }
                    break;
                default:
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                    break;
            }

            // Handling headers
            if ($this->headers && is_array($this->headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            return array("is_error" => false, "value" => $ch);
        } catch (Throwable $th) {
            return array("is_error" => true, "message" => $th->getMessage() . " on line: " . $th->getLine());
        }
    }

    public function get_response($rest_request) {
        $response = curl_exec($rest_request);

        if (curl_errno($rest_request)) {
            return array("is_error" => true, "message" => curl_error($rest_request));
        }

        $http_code = curl_getinfo($rest_request, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($rest_request, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        curl_close($rest_request);

        $headers_array = [];
        $header_lines = explode("\r\n", $headers);
        foreach ($header_lines as $header_line) {
            if (!empty($header_line)) {
                $header_parts = explode(": ", $header_line, 2);
                if (count($header_parts) == 2) {
                    $headers_array[$header_parts[0]] = $header_parts[1];
                }
            }
        }

        $response_data = array(
            "status_code" => $http_code,
            "content" => $content,
            "headers" => $headers_array
        );

        return array("is_error" => false, "value" => $response_data);
    }
}
?>
