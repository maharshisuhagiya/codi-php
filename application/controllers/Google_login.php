<?php
defined("BASEPATH") or exit("No direct script access allowed");

class Google_login extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model("google_login_model");
    }

    function login() {
        // echo CI_VERSION; die;
        include_once APPPATH . "libraries/vendor/autoload.php";
        $google_client = new Google_Client();
        // echo "<pre>"; print_R($google_client); die;
        $google_client->setClientId("469134878464-hssim9fl7sksoeuaa97hg4l1keth5l55.apps.googleusercontent.com"); //Define your ClientID

        $google_client->setClientSecret("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"); //Define your Client Secret Key

        $google_client->setRedirectUri("https://appsmaniaonline.tech/eshop/"); //Define your Redirect Uri

        $google_client->addScope("email");

        $google_client->addScope("profile");

        if (isset($_GET["code"])) {
            $token = $google_client->fetchAccessTokenWithAuthCode(
                $_GET["code"]
            );

            if (!isset($token["error"])) {
                $google_client->setAccessToken($token["access_token"]);

                $this->session->set_userdata(
                    "access_token",
                    $token["access_token"]
                );

                $google_service = new Google_Service_Oauth2($google_client);

                $data = $google_service->userinfo->get();

                $current_datetime = date("Y-m-d H:i:s");

                if (
                    $this->google_login_model->Is_already_register($data["id"])
                ) {
                    //update data
                    $user_data = [
                        "username" => $data["given_name"].$data["family_name"],
                        "email" => $data["email"],
                        "image" => $data["picture"],
                        "updated_at" => $current_datetime,
                    ];

                    $this->google_login_model->Update_user_data(
                        $user_data,
                        $data["id"]
                    );
                } else {
                    //insert data
                    $user_data = [
                        "username" => $data["given_name"].$data["family_name"],
                        "login_oauth_uid" => $data["id"],
                        "email" => $data["email"],
                        "image" => $data["picture"],
                        "created_at" => $current_datetime,
                        "mobile" =>rand(1111111111,9999999999),
                        "password" => password_hash(rand(11111111,99999999), PASSWORD_BCRYPT),
                    ];

                    $this->google_login_model->Insert_user_data($user_data);
                }
                // $this->session->set_userdata("user_data", $user_data);
            }
        }
        $login_button = "";
        if (!$this->session->userdata("access_token")) {
            $login_button = '<a href="' . $google_client->createAuthUrl() .'"><img src="'.base_url() . 'assets/sign-in-with-google.png" /></a>';
            $data["login_button"] = $login_button;
            $this->load->view("google_login", $data);
        } else {
            $this->load->view("google_login", $data);
        }
    }

    function logout() {
        $this->session->unset_userdata("access_token");

        $this->session->unset_userdata("user_data");

        redirect("google_login/login");
    }
}

