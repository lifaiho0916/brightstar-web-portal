<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Announcement;
use App\Models\Shipment;
use App\Models\Staff;
use App\Models\Commission_Type;

class AppController extends BaseController
{
    public function __construct() {
        date_default_timezone_set("Asia/Kuala_Lumpur");
        $this->datetime = date("Y-m-d H:i:s");
        $this->date = date("Y-m-d");
        $this->db = \Config\Database::connect();
        
        $this->announcementModel = new Announcement();
        $this->shipmentModel = new Shipment();
        $this->staffModel = new Staff();
        $this->commissionTypeModel = new Commission_Type();

        $this->driver_image_url = "uploads/mobile_app/images/driver/";
        $this->announcement_image_url = "uploads/announcements/";
    }
    
    public function login () { // done
        $username = $_POST['id'];
        $password = $_POST['password'];
        
        $result = $this->db->query("SELECT * FROM bs_staffs WHERE username = '$username' AND role = 2")->getResultArray();
        
        if (COUNT($result) > 0) {
            $driver = $result[0];
            
            if ($password == $driver['password']) {
                $response['driver'] = array();
                
                $n = array();
                $n["id"]                    = $driver["id"];
                $n["name"]                  = $driver["name"];
                $n["phone"]                 = $driver["phone"];
                $n["username"]              = $driver["username"];
                $n["commission"]            = $driver["commission"];
                $n["created_at"]            = $driver["created_at"];
                $n["picture"]               = base_url($this->driver_image_url.$driver["picture"]);
                
                array_push($response['driver'], $n);
                
                echo json_encode($response);
            } else {
                echo "Invalid id or password !";
            }
        } else {
            echo "Account does not exist !";
        }
    }
    
    public function loadProfile () { // done
        $id = $_POST['id'];
    
        $result = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$id'")->getResultArray();
        
        if (COUNT($result) > 0) {
            $response['profile'] = array();
            
            foreach ($result as $row) {
                $n = array();
                $n = array();
                $n["id"]                    = $row["id"];
                $n["name"]                  = $row["name"];
                $n["phone"]                 = $row["phone"];
                $n["username"]              = $row["username"];
                $n["commission"]            = $row["commission"];
                $n["created_at"]            = $row["created_at"];
                $n["picture"]               = base_url($this->driver_image_url.$row["picture"]);
                
                array_push($response["profile"], $n);
            }
            echo json_encode($response);
        } else {
            echo "empty";
        }
    }
    
    public function uploadNewProfilePicture () { // done
        $id = $_POST['staff_id'];
        
        $encoded_string = $_POST["picture_string"];
        $decoded_string = base64_decode($encoded_string);
        
        $filename = $id.'_'.date('Ymd').'_'.date('His').'.png';
        $filepath = '../public/uploads/mobile_app/images/driver/'.$filename;
        
        if (file_put_contents($filepath, $decoded_string)) {
            $result = $this->db->query("UPDATE bs_staffs SET picture = '$filename' WHERE id = '$id'");
            
            if ($result) {
                $response['picture'] = array();
                
                $n = array();
                $n["filename"]   = base_url($this->driver_image_url.$filename);
                
                array_push($response['picture'], $n);
                echo json_encode($response);
            } else {
                echo "Failed to upload image";
            }
        } else {
            echo "Failed to upload image.";
        }
    }
    
    public function withdrawCommission () { // done
        $id = $_POST['id'];
    
        $result = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$id'")->getResultArray();
        
        if (COUNT($result) > 0) {
            $commission = $result[0]['commission'];
            $datetime = date("Y-m-d H:i:s");
            
            $result2 = $this->db->query("INSERT INTO bs_commissions (staff_id, amount, status, is_refunded, shipment_id, created_at) VALUES ('$id', '$commission', 0, 0, 0, '$datetime')");

            if ($result2) {
                $result3 = $this->db->query("UPDATE bs_staffs SET commission = 0 WHERE id = '$id'");
            
                if ($result3) {
                    echo "success";
                } else {
                    echo "failed";
                }
            } else {
                echo "failed";
            }
        } else {
            echo "failed";
        }
    }
    
    public function loadAllCommissionHistory () { // done
        $staff_id = $_POST['id'];
    
        $result = $this->db->query("SELECT * FROM bs_shipments WHERE shipment_status = 4 AND (driver = '$staff_id' OR delivery_partner = '$staff_id') ORDER BY id DESC")->getResultArray();
        
        if (COUNT($result) > 0) {
            $response['commission'] = array();
            foreach ($result as $shipment) {
                $n = array();
                $n["shipment_id"]           = $shipment["id"] ?? "";
                $n["reference_id"]          = $shipment["reference_id"] ?? "";
                $n["service_level"]         = $shipment["service_level"] ?? "";
                $n["shipment_no"]           = $shipment["shipment_no"] ?? "";
                $n["driver"]                = $shipment["driver"] ?? "";
                $n["driver_commission"]     = $shipment["driver_commission"] ?? "0.00";
                $n["delivery_partner"]      = $shipment["delivery_partner"] ?? "";
                
                if ($shipment['delivery_partner'] != "") {
                    $result3 = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$shipment[delivery_partner]'")->getResultArray();
                    
                    $n["delivery_partner_name"] = $result3[0]["name"] ?? "";
                } else {
                    $n["delivery_partner_name"] = "";
                }
                
                $n["status"]                = $shipment["status"] ?? "";
                $n["shipment_status"]       = $shipment["shipment_status"] ?? "";
                $n["origin"]                = $shipment["origin"] ?? "";
                $n["destination"]           = $shipment["destination"] ?? "";
                $n["location"]              = $shipment["location"] ?? "";
                $n["weight"]                = $shipment["weight"] ?? "";
                $n["quantity"]              = $shipment["quantity"] ?? "";
                
                $n["receiver_name"]         = $shipment["receiver_name"] ?? "";
                $n["receiver_phone"]        = $shipment["receiver_phone"] ?? "";
                $n["receiver_address"]      = $shipment["receiver_address"] ?? "";
                $n["receiver_city"]         = $shipment["receiver_city"] ?? "";
                $n["receiver_state"]        = $shipment["receiver_state"] ?? "";
                $n["receiver_country"]      = $shipment["receiver_country"] ?? "";
                $n["receiver_postcode"]     = $shipment["receiver_postcode"] ?? "";
                
                $n["sender_name"]           = $shipment["sender_name"] ?? "";
                $n["sender_phone"]          = $shipment["sender_phone"] ?? "";
                $n["sender_address_line1"]  = $shipment["sender_address_line1"] ?? "";
                $n["sender_address_line2"]  = $shipment["sender_address_line2"] ?? "";
                $n["sender_state"]          = $shipment["sender_state"] ?? "";
                $n["sender_city"]           = $shipment["sender_city"] ?? "";
                $n["sender_country"]        = $shipment["sender_country"] ?? "";
                $n["sender_postcode"]       = $shipment["sender_postcode"] ?? "";
    
                $n["created_at"]            = date("d/m/Y", strtotime($shipment["created_at"]));
                
                array_push($response['commission'], $n);
            }
            
            echo json_encode($response);
        } else {
            echo "No commission history yet.";
        }
    }
    
    public function loadSingleCommissionHistory () { // done
        $shipment_id = $_POST['id'];
        $staff_id = $_POST['staff_id'];
        
        $result = $this->db->query("SELECT * FROM bs_shipments WHERE id = $shipment_id")->getResultArray();
        $result2 = $this->db->query("SELECT * FROM bs_staffs WHERE role = 2 AND id != '$staff_id' ORDER BY id DESC")->getResultArray();
        
        if (COUNT($result) > 0) {
            $response['commission'] = array();
            
            foreach ($result as $shipment) {
                $n = array();
                
                $n["shipment_id"]           = $shipment["id"] ?? "";
                $n["reference_id"]          = $shipment["reference_id"] ?? "";
                $n["service_level"]         = $shipment["service_level"] ?? "";
                $n["shipment_no"]           = $shipment["shipment_no"] ?? "";
                $n["driver"]                = $shipment["driver"] ?? "";
                
                if ($shipment['driver'] != "") {
                    $resultx = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$shipment[driver]'")->getResultArray();
                    
                    $n["driver_name"] = $resultx[0]["name"] ?? "";
                } else {
                    $n["driver_name"] = "";
                }
    
                $n["delivery_partner"]                 = $shipment["delivery_partner"] ?? "";
                $n["driver_commission"]                = $shipment["driver_commission"] ?? 0.00;
                $n["delivery_partner_commission"]      = $shipment["delivery_partner_commission"] ?? 0.00;
                
                if ($shipment['delivery_partner'] != "") {
                    $result3 = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$shipment[delivery_partner]'")->getResultArray();
                    
                    $n["delivery_partner_name"] = $result3[0]["name"] ?? "";
                } else {
                    $n["delivery_partner_name"] = "";
                }
                
                $n["status"]                = $shipment["status"] ?? "";
                $n["shipment_status"]       = $shipment["shipment_status"] ?? "";
                $n["pickup_picture"]        = $shipment["pickup_picture"] ?? "";
                $n["drop_picture"]          = $shipment["drop_picture"] ?? "";
                $n["pickup_date"]           = $shipment["pickup_date"] == "" ? "" : date("d/m/Y H:i:s", strtotime($shipment["pickup_date"]));
                $n["drop_date"]             = $shipment["drop_date"] == "" ? "" : date("d/m/Y H:i:s", strtotime($shipment["drop_date"]));
                $n["origin"]                = $shipment["origin"] ?? "";
                $n["destination"]           = $shipment["destination"] ?? "";
                $n["location"]              = $shipment["location"] ?? "";
                $n["weight"]                = $shipment["weight"] ?? "";
                $n["quantity"]              = $shipment["quantity"] ?? "";
                
                $n["receiver_name"]         = $shipment["receiver_name"] ?? "";
                $n["receiver_phone"]        = $shipment["receiver_phone"] ?? "";
                $n["receiver_address"]      = $shipment["receiver_address"] ?? "";
                $n["receiver_city"]         = $shipment["receiver_city"] ?? "";
                $n["receiver_state"]        = $shipment["receiver_state"] ?? "";
                $n["receiver_country"]      = $shipment["receiver_country"] ?? "";
                $n["receiver_postcode"]     = $shipment["receiver_postcode"] ?? "";
                
                $n["sender_name"]           = $shipment["sender_name"] ?? "";
                $n["sender_phone"]          = $shipment["sender_phone"] ?? "";
                $n["sender_address_line1"]  = $shipment["sender_address_line1"] ?? "";
                $n["sender_address_line2"]  = $shipment["sender_address_line2"] ?? "";
                $n["sender_state"]          = $shipment["sender_state"] ?? "";
                $n["sender_city"]           = $shipment["sender_city"] ?? "";
                $n["sender_country"]        = $shipment["sender_country"] ?? "";
                $n["sender_postcode"]       = $shipment["sender_postcode"] ?? "";
    
                $n["created_at"]            = date("d/m/Y", strtotime($shipment["created_at"]));
                
                array_push($response['commission'], $n);
            }
            
            $response['driver'] = array();
            foreach ($result2 as $driver) {
                $n = array();
                $n["driver_id"]             = $driver["id"] ?? "";
                $n["name"]                  = $driver["name"] ?? "";
    
                array_push($response['driver'], $n);
            }
            
            echo json_encode($response);
        } else {
            echo "No information yet.";
        }
    }
    
    public function loadAllShipment () {
        $staff_id = $_POST['id'];
    
        $result = $this->db->query("SELECT * FROM bs_shipments WHERE shipment_status != 4 AND (driver = '$staff_id' OR delivery_partner = '$staff_id') ORDER BY id DESC")->getResultArray();
        
        if (COUNT($result) > 0) {
            $response['shipment'] = array();
            foreach ($result as $shipment) {
                $n = array();
                $n["shipment_id"]           = $shipment["id"] ?? "";
                $n["reference_id"]          = $shipment["reference_id"] ?? "";
                $n["service_level"]         = $shipment["service_level"] ?? "";
                $n["shipment_no"]           = $shipment["shipment_no"] ?? "";
                $n["driver"]                = $shipment["driver"] ?? "";
                $n["delivery_partner"]      = $shipment["delivery_partner"] ?? "";
                
                if ($shipment['delivery_partner'] != "") {
                    $result3 = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$shipment[delivery_partner]'")->getResultArray();
                    
                    $n["delivery_partner_name"] = $result3[0]["name"] ?? "";
                } else {
                    $n["delivery_partner_name"] = "";
                }
                
                $n["status"]                = $shipment["status"] ?? "";
                $n["shipment_status"]       = $shipment["shipment_status"] ?? "";
                $n["origin"]                = $shipment["origin"] ?? "";
                $n["destination"]           = $shipment["destination"] ?? "";
                $n["location"]              = $shipment["location"] ?? "";
                $n["weight"]                = $shipment["weight"] ?? "";
                $n["quantity"]              = $shipment["quantity"] ?? "";
                
                $n["receiver_name"]         = $shipment["receiver_name"] ?? "";
                $n["receiver_phone"]        = $shipment["receiver_phone"] ?? "";
                $n["receiver_address"]      = $shipment["receiver_address"] ?? "";
                $n["receiver_city"]         = $shipment["receiver_city"] ?? "";
                $n["receiver_state"]        = $shipment["receiver_state"] ?? "";
                $n["receiver_country"]      = $shipment["receiver_country"] ?? "";
                $n["receiver_postcode"]     = $shipment["receiver_postcode"] ?? "";
                
                $n["sender_name"]           = $shipment["sender_name"] ?? "";
                $n["sender_phone"]          = $shipment["sender_phone"] ?? "";
                $n["sender_address_line1"]  = $shipment["sender_address_line1"] ?? "";
                $n["sender_address_line2"]  = $shipment["sender_address_line2"] ?? "";
                $n["sender_state"]          = $shipment["sender_state"] ?? "";
                $n["sender_city"]           = $shipment["sender_city"] ?? "";
                $n["sender_country"]        = $shipment["sender_country"] ?? "";
                $n["sender_postcode"]       = $shipment["sender_postcode"] ?? "";
    
                $n["created_at"]            = date("d/m/Y", strtotime($shipment["created_at"]));
                
                array_push($response['shipment'], $n);
            }
            
            echo json_encode($response);
        } else {
            echo "No shipment yet.";
        }
    }
    
    public function loadSingleShipment () {
        $shipment_id = $_POST['id'];
        $staff_id = $_POST['staff_id'];
        
        $result = $this->db->query("SELECT * FROM bs_shipments WHERE id = $shipment_id")->getResultArray();
        $result2 = $this->db->query("SELECT * FROM bs_staffs WHERE role = 2 AND id != '$staff_id' ORDER BY id DESC")->getResultArray();
        
        if (COUNT($result) > 0) {
            $response['shipment'] = array();
            
            foreach ($result as $shipment) {
                $n = array();
                
                $n["shipment_id"]           = $shipment["id"] ?? "";
                $n["reference_id"]          = $shipment["reference_id"] ?? "";
                $n["service_level"]         = $shipment["service_level"] ?? "";
                $n["shipment_no"]           = $shipment["shipment_no"] ?? "";
                $n["driver"]                = $shipment["driver"] ?? "";
                
                if ($shipment['driver'] != "") {
                    $resultx = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$shipment[driver]'")->getResultArray();
                    
                    $n["driver_name"] = $resultx[0]["name"] ?? "";
                } else {
                    $n["driver_name"] = "";
                }
    
                $n["delivery_partner"]                 = $shipment["delivery_partner"] ?? "";
                $n["driver_commission"]                = $shipment["driver_commission"] ?? 0.00;
                $n["delivery_partner_commission"]      = $shipment["delivery_partner_commission"] ?? 0.00;
                
                if ($shipment['delivery_partner'] != "") {
                    $result3 = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$shipment[delivery_partner]'")->getResultArray();
                    
                    $n["delivery_partner_name"] = $result3[0]["name"] ?? "";
                } else {
                    $n["delivery_partner_name"] = "";
                }
                
                $n["status"]                = $shipment["status"] ?? "";
                $n["shipment_status"]       = $shipment["shipment_status"] ?? "";
                $n["pickup_picture"]        = $shipment["pickup_picture"] ?? "";
                $n["drop_picture"]          = $shipment["drop_picture"] ?? "";
                $n["pickup_date"]           = $shipment["pickup_date"] == "" ? "" : date("d/m/Y H:i:s", strtotime($shipment["pickup_date"]));
                $n["drop_date"]             = $shipment["drop_date"] == "" ? "" : date("d/m/Y H:i:s", strtotime($shipment["drop_date"]));
                $n["origin"]                = $shipment["origin"] ?? "";
                $n["destination"]           = $shipment["destination"] ?? "";
                $n["location"]              = $shipment["location"] ?? "";
                $n["weight"]                = $shipment["weight"] ?? "";
                $n["quantity"]              = $shipment["quantity"] ?? "";
                
                $n["receiver_name"]         = $shipment["receiver_name"] ?? "";
                $n["receiver_phone"]        = $shipment["receiver_phone"] ?? "";
                $n["receiver_address"]      = $shipment["receiver_address"] ?? "";
                $n["receiver_city"]         = $shipment["receiver_city"] ?? "";
                $n["receiver_state"]        = $shipment["receiver_state"] ?? "";
                $n["receiver_country"]      = $shipment["receiver_country"] ?? "";
                $n["receiver_postcode"]     = $shipment["receiver_postcode"] ?? "";
                
                $n["sender_name"]           = $shipment["sender_name"] ?? "";
                $n["sender_phone"]          = $shipment["sender_phone"] ?? "";
                $n["sender_address_line1"]  = $shipment["sender_address_line1"] ?? "";
                $n["sender_address_line2"]  = $shipment["sender_address_line2"] ?? "";
                $n["sender_state"]          = $shipment["sender_state"] ?? "";
                $n["sender_city"]           = $shipment["sender_city"] ?? "";
                $n["sender_country"]        = $shipment["sender_country"] ?? "";
                $n["sender_postcode"]       = $shipment["sender_postcode"] ?? "";
    
                $n["created_at"]            = date("d/m/Y", strtotime($shipment["created_at"]));
                
                array_push($response['shipment'], $n);
            }
            
            $response['driver'] = array();
            foreach ($result2 as $driver) {
                $n = array();
                $n["driver_id"]             = $driver["id"] ?? "";
                $n["name"]                  = $driver["name"] ?? "";
    
                array_push($response['driver'], $n);
            }
            
            echo json_encode($response);
        } else {
            echo "No shipment yet.";
        }
    }
    
    public function assignDeliveryPartner () {
        $shipment_id = $_POST['id'];
        $staff_id = $_POST['staff_id'];
        
        $result = $this->db->query("UPDATE bs_shipments SET delivery_partner = '$staff_id', delivery_partner_commission = 30 WHERE id = '$shipment_id'");
        
        if ($result) {
            echo "success";
        } else {
            echo "Failed to assign delivery partner";
        }
    }
    
    public function submitReport () {
        $order_id = $_POST['order_id'];
        $report = $_POST['report'];
        $reported_at = date('Y-m-d H:i:s');
        
        $result = $this->db->query("UPDATE orders SET report = '$report', reported_at = '$reported_at' WHERE order_id = '$order_id'");
        
        if ($result) {
            echo "success";
        } else {
            echo "failed";
        }
    }
    
    public function submitPicture () {
        $shipment_id = $_POST['id'];
        $shipment_type = $_POST['type'];
        $encoded_string_arr = json_decode($_POST["picture_string_arr"]);
        $quantity = $_POST['quantity'] ?? 1;
        
        $filename_arr = array();
        $response_arr = array();
        
        $i = 0;
        foreach ($encoded_string_arr as $encoded_string) {
            $i++;
            $decoded_string = base64_decode($encoded_string);
            $f = finfo_open();
            $mime_type = finfo_buffer($f, $decoded_string, FILEINFO_MIME_TYPE);
            $split = explode( '/', $mime_type );
            $type = $split[1]; 
            
            $filename = date('Ymd').'_'.date('his').'_'.$shipment_id.'('.$i.').'.$type;
            $filepath = '../public/uploads/mobile_app/images/shipment/'.$filename;
    
            if (file_put_contents($filepath, $decoded_string)) {
                array_push($filename_arr, $filename);
                array_push($response_arr, "success");
            } else {
                array_push($response_arr, "failed");
            }
        }
    
        if (in_array("failed", $response_arr)) {
            echo "failed";
        } else {
            $images = implode(",", $filename_arr);
            $completed_at = date('Y-m-d H:i:s');
            
            $driver_id = $_POST['driver_id'];
            $delivery_partner_id = $_POST['delivery_partner_id'];
            $commission_driver = 30.00;
            $commission_delivery_partner = 20.00;
            $datetime = date("Y-m-d H:i:s");
    
            switch ($shipment_type) {
                case "pickup": 
                    $result = $this->db->query("UPDATE bs_shipments SET quantity = '$quantity', shipment_status = 3, pickup_picture = '$images', pickup_date = '$completed_at' WHERE id = '$shipment_id'");
                    
                    // Main Driver Commission
                    $this->db->query("INSERT INTO bs_shipments_commissions (shipment_id, staff_id, commission_type_id, commission, type, role, created_at) VALUES ('$shipment_id', '$driver_id', 0, '$commission_driver', 0, 0, '$datetime')");
                    $this->db->query("UPDATE bs_staffs SET commission = commission + $commission_driver WHERE id = '$driver_id'");
                    // Main Driver Commission
                    
                    // Delivery Partner Commission
                    $this->db->query("INSERT INTO bs_shipments_commissions (shipment_id, staff_id, commission_type_id, commission, type, role, created_at) VALUES ('$shipment_id', '$delivery_partner_id', 0, '$commission_delivery_partner', 0, 1, '$datetime')");
                    $this->db->query("UPDATE bs_staffs SET commission = commission + $commission_delivery_partner WHERE id = '$delivery_partner_id'");
                    // Delivery Partner Commission
                    
                    break;
                case "drop":
                    $result = $this->db->query("UPDATE bs_shipments SET quantity = '$quantity', shipment_status = 4, drop_picture = '$images', drop_date = '$completed_at' WHERE id = '$shipment_id'");
                    
                    // Main Driver Commission
                    $this->db->query("INSERT INTO bs_shipments_commissions (shipment_id, staff_id, commission_type_id, commission, type, role, created_at) VALUES ('$shipment_id', '$driver_id', 0, '$commission_driver', 1, 0, '$datetime')");
                    $this->db->query("UPDATE bs_staffs SET commission = commission + $commission_driver WHERE id = '$driver_id'");
                    // Main Driver Commission
                    
                    // Delivery Partner Commission
                    $this->db->query("INSERT INTO bs_shipments_commissions (shipment_id, staff_id, commission_type_id, commission, type, role, created_at) VALUES ('$shipment_id', '$delivery_partner_id', 0, '$commission_delivery_partner', 1, 1, '$datetime')");
                    $this->db->query("UPDATE bs_staffs SET commission = commission + $commission_delivery_partner WHERE id = '$delivery_partner_id'");
                    // Delivery Partner Commission
                    break;
            }
            
            if ($result) {
                echo "success";
            } else {
                echo "failed";
            }
        }
    }
    
    public function loadAllShipmentHistory () {
        $staff_id = $_POST['id'];
        
        $result = $this->db->query("SELECT * FROM bs_shipments WHERE shipment_status = 4 AND (driver = '$staff_id' OR delivery_partner = '$staff_id') ORDER BY id DESC")->getResultArray();
        
        if (COUNT($result) > 0) {
            $response['shipment'] = array();
            
            foreach ($result as $shipment) {
                $n = array();
                $n["shipment_id"]           = $shipment["id"] ?? "";
                $n["reference_id"]          = $shipment["reference_id"] ?? "";
                $n["service_level"]         = $shipment["service_level"] ?? "";
                $n["shipment_no"]           = $shipment["shipment_no"] ?? "";
                $n["driver"]                = $shipment["driver"] ?? "";
                $n["delivery_partner"]      = $shipment["delivery_partner"] ?? "";
                
                if ($shipment['delivery_partner'] != "") {
                    $result3 = $this->db->query("SELECT * FROM bs_staffs WHERE id = '$shipment[delivery_partner]'")->getResultArray();
                    
                    $n["delivery_partner_name"] = $result3[0]["name"] ?? "";
                } else {
                    $n["delivery_partner_name"] = "";
                }
                
                $n["status"]                = $shipment["status"] ?? "";
                $n["shipment_status"]       = $shipment["shipment_status"] ?? "";
                $n["origin"]                = $shipment["origin"] ?? "";
                $n["destination"]           = $shipment["destination"] ?? "";
                $n["location"]              = $shipment["location"] ?? "";
                $n["weight"]                = $shipment["weight"] ?? "";
                $n["quantity"]              = $shipment["quantity"] ?? "";
                
                $n["receiver_name"]         = $shipment["receiver_name"] ?? "";
                $n["receiver_phone"]        = $shipment["receiver_phone"] ?? "";
                $n["receiver_address"]      = $shipment["receiver_address"] ?? "";
                $n["receiver_city"]         = $shipment["receiver_city"] ?? "";
                $n["receiver_state"]        = $shipment["receiver_state"] ?? "";
                $n["receiver_country"]      = $shipment["receiver_country"] ?? "";
                $n["receiver_postcode"]     = $shipment["receiver_postcode"] ?? "";
                
                $n["sender_name"]           = $shipment["sender_name"] ?? "";
                $n["sender_phone"]          = $shipment["sender_phone"] ?? "";
                $n["sender_address_line1"]  = $shipment["sender_address_line1"] ?? "";
                $n["sender_address_line2"]  = $shipment["sender_address_line2"] ?? "";
                $n["sender_state"]          = $shipment["sender_state"] ?? "";
                $n["sender_city"]           = $shipment["sender_city"] ?? "";
                $n["sender_country"]        = $shipment["sender_country"] ?? "";
                $n["sender_postcode"]       = $shipment["sender_postcode"] ?? "";
    
                $n["created_at"]            = date("d/m/Y", strtotime($shipment["created_at"]));
                
                array_push($response['shipment'], $n);
            }
            
            echo json_encode($response);
        } else {
            echo "No shipment history yet.";
        }
    }

    public function sortDate($val1, $val2) {
        if ($val1['created_at'] == $val2['created_at']) {
            return 0;
        }
    
        return (strtotime($val1['created_at']) < strtotime($val2['created_at'])) ? -1 : 1;
    }
}
