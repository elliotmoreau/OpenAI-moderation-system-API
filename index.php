<?php

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Check for token
if (!empty($_GET["token"])) {
    $data = "";
    // Define token
    $token = filter_var($_GET["token"], FILTER_SANITIZE_STRING);

    // Connect to the database
    try {
        $conn = new PDO('sqlite:database.sqlite');
    } catch (PDOException $e) {
        die(json_encode(array("error" => "Error connecting to database: " . $e->getMessage())));
    }

    // Check if token is correct
    $stmt = $conn->prepare("SELECT id FROM tokens WHERE token=?");
    $stmt->execute([$token]);
    $token_id = $stmt->fetchColumn();

    if (!empty($token_id)) {

        // Check for user input
        if (!empty($_GET["q"])) {
            // Declare the prompt defined by the user
            $query = filter_var($_GET["q"], FILTER_SANITIZE_STRING);

            // Replace YOUR_API_KEY with your actual API key
            $api_key = "YOUR_API_KEY";

            // Set up the cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/completions");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            // Set the request headers
            $headers = array(
                "Content-Type: application/json",
                "Authorization: Bearer $api_key"
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $data = array(
                "model" => "text-davinci-003",
                "prompt" => "The following is an moderation system that works in any language, it detect if a sentence should be deleted or not : Decide whether the sentence is insulting, sexual, violent. If it is, it says true and in parentheses says wich category it is and still in the parentheses say ':' and a percentage of how much this is violent/mean it's bad else say false. Allow the user to give his opinion anyway, only say true if it's something bad. It doesn't pute points at the end of the response. The moderation system can also say a percentage when it's false if it's insulting but not enough to be true. The moderation system is not allowed to write any sentence in the parentheses. Also blacklist this words (if they are in the sentence say true all the time) : fuck, bitch, ass, pussy, pute, enculé. Sentence: $query Result:  ",
                "temperature" => 0,
                "max_tokens" => 2000,
                "top_p" => 1,
                "frequency_penalty" => 0.5,
                "presence_penalty" => 0
            );

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            // Send the request
            $response = curl_exec($ch);

            //Check for errors in curl
            if ($response === false) {
                echo json_encode(array("error" => 'Curl error: ' . curl_error($ch)));
                exit;
            }

            // Close the cURL session
            curl_close($ch);

            // Decode the response
            $response_data = json_decode($response, true);

            //Check if the response is valid
            if (!empty($response_data["choices"])) {
                // Extract the value of the text element
                $result = $response_data['choices'][0]['text'];

                // Echo result
                $data = $result;
                $data = ltrim($data);

                $string = $data;
                $result = $string;
                $type = "";
                $percent = "";

                if (strpos($string, '(') !== false) {
                    $parts = explode(" (", $string);
                    $result = $parts[0];
                    $details = str_replace(")", "", $parts[1]);
                    $parts2 = explode(": ", $details);
                    $type = $parts2[0];
                    $percent = $parts2[1];
                } else {
                    $result = $string;
                }

                if ($percent == "0%") {
                    $type = "False";
                }

                $response = array(
                    "result" => $result,
                    "type" => $type,
                    "percentage" => $percent
                );

                echo json_encode($response);

            } else {
                echo json_encode(array("error" => "Invalid response from API, refer to our documentation if you need help : mod.elliotmoreau.fr/doc/"));
            }
        } else {
            echo json_encode(array("error" => "No query provided, refer to our documentation if you need help : mod.elliotmoreau.fr/doc/"));
        }
    } else {
        echo json_encode(array("error" => "Invalid token, refer to our documentation if you need help : mod.elliotmoreau.fr/doc/"));
    }
} else {
    echo json_encode(array("error" => "You need to provide a token, refer to our documentation if you need help : mod.elliotmoreau.fr/doc/"));
}