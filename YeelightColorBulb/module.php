<?
// Klassendefinition
class YeelightColorBulb extends IPSModule {

    const READ_STATES_CMD = "444";
    const POWER_CMD = "10";
    const BRIGHTNESS_CMD = "20";
    const COLOR_TEMP_CMD = "30";
    const RGB_CMD = "40";
    const HUE_CMD = "50";
    const SAT_CMD = "60";
    const COLOR_MODE_CMD = "70";

    // Der Konstruktor des Moduls
    public function __construct($InstanceID) {
        // Diese Zeile nicht löschen
        parent::__construct($InstanceID);

        // Selbsterstellter Code
    }

    public function Create() {
        parent::Create();

        $this->RegisterPropertyString("ipadress", "");

        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}"); // CLIENT SOCKET

        if (!IPS_VariableProfileExists("Yeelight.ColorTemperature")) {
            IPS_CreateVariableProfile("Yeelight.ColorTemperature", 1);
        }
        IPS_SetVariableProfileValues("Yeelight.ColorTemperature", 1700, 6500, 100);

        if (!IPS_VariableProfileExists("Yeelight.RGB")) {
            IPS_CreateVariableProfile("Yeelight.RGB", 1);
        }

        IPS_SetVariableProfileValues("Yeelight.RGB", 0, 16777215, 1000);


        if (!IPS_VariableProfileExists("Yeelight.HUE")) {
            IPS_CreateVariableProfile("Yeelight.HUE", 1);
        }
        IPS_SetVariableProfileValues("Yeelight.HUE", 0, 359, 1);


        if (!IPS_VariableProfileExists("Yeelight.ColorMode")) {
            IPS_CreateVariableProfile("Yeelight.ColorMode", 1);
        }
        IPS_SetVariableProfileValues("Yeelight.ColorMode", 0, 5, 0);
        IPS_SetVariableProfileAssociation("Yeelight.ColorMode", 0, "Default", "", 0);
        IPS_SetVariableProfileAssociation("Yeelight.ColorMode", 1, "Color Temperature", "", 0);
        IPS_SetVariableProfileAssociation("Yeelight.ColorMode", 2, "RGB", "", 0);
        IPS_SetVariableProfileAssociation("Yeelight.ColorMode", 3, "HSV", "", 0);
        IPS_SetVariableProfileAssociation("Yeelight.ColorMode", 4, "Color Flow", "", 0);
        IPS_SetVariableProfileAssociation("Yeelight.ColorMode", 5, "Night Light (Ceiling only)", "", 0);
    }


    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        $this->RegisterVariableBoolean("power", "Power", "~Switch", 0);
        $this->RegisterVariableInteger("bright", "Brightness", "~Intensity.100", 1);
        $this->RegisterVariableInteger("ct", "Color Temperature", "Yeelight.ColorTemperature", 2);
        $this->RegisterVariableInteger("rgb", "Color", "Yeelight.RGB", 3);
        $this->RegisterVariableInteger("hue", "Hue", "Yeelight.HUE", 4);
        $this->RegisterVariableInteger("sat", "Saturation", "~Intensity.100", 5);
        $this->RegisterVariableInteger("color_mode", "Color Mode", "Yeelight.ColorMode", 6);

        $this->GetConfigurationForParent();

        $this->EnableAction("power");
        $this->EnableAction("bright");
        $this->EnableAction("ct");
        $this->EnableAction("rgb");
        $this->EnableAction("hue");
        $this->EnableAction("sat");
        $this->EnableAction("color_mode");
    }

    public function GetConfigurationForParent()
    {
        $host = $this->ReadPropertyString("ipadress");
        $port = 55443;
        return "{\"Host\": \"$host\", \"Port\": \"$port\"}";
    }


    // Lese alle Konfigurationsdaten aus
    public function readStatesFromDevice() {
        $this->buildAndSendCommand(self::READ_STATES_CMD, 'get_prop', array('power', 'bright', 'ct', 'rgb', 'hue', 'sat', 'color_mode'));
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $payload = json_decode($data->Buffer);
        //27/12/2017 16:06:33 | Receive | {"id":1, "result":["off","27","2945","255","359","100","2"]}
        // this is our Datafetch from Configuration GUI, which has a fixed sorting of values
        if (isset($payload->id) && self::READ_STATES_CMD == $payload->id) {
            SetValueBoolean(IPS_GetObjectIDByIdent("power", $this->InstanceID), 'on' == $payload->result[0] ? TRUE : FALSE);
            SetValueInteger(IPS_GetObjectIDByIdent("bright", $this->InstanceID), $payload->result[1]);
            SetValueInteger(IPS_GetObjectIDByIdent("ct", $this->InstanceID), $payload->result[2]);
            if (!empty($payload->result[3])) {
                SetValueInteger(IPS_GetObjectIDByIdent("rgb", $this->InstanceID), $payload->result[3]);
            }
            if (!empty($payload->result[4])) {
                SetValueInteger(IPS_GetObjectIDByIdent("hue", $this->InstanceID), $payload->result[4]);
            }
            if (!empty($payload->result[5])) {
                SetValueInteger(IPS_GetObjectIDByIdent("sat", $this->InstanceID), $payload->result[5]);
            }
            SetValueInteger(IPS_GetObjectIDByIdent("color_mode", $this->InstanceID), $payload->result[6]);
            return;
        }
        //IPS_LogMessage("Receiver", utf8_decode($data->Buffer));
        //27/12/2017 17:40:01 | Receiver | {"method":"props","params":{"power":"off"}}
        if (isset($payload->method) && 'props' == $payload->method) {
            foreach ($payload->params as $key => $val) {
                print_r($key . " " . $val);
                if ('power' == $key) {
                    SetValueBoolean(IPS_GetObjectIDByIdent("power", $this->InstanceID), 'on' == $val ? TRUE : FALSE);
                } else if ('bright' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("bright", $this->InstanceID), $val);
                } else if ('ct' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("ct", $this->InstanceID), $val);
                } else if ('rgb' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("rgb", $this->InstanceID), $val);
                } else if ('hue' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("hue", $this->InstanceID), $val);
                } else if ('sat' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("sat", $this->InstanceID), $val);
                } else if ('color_mode' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("color_mode", $this->InstanceID), $val);
                }
            }
        } else if ('ok' == $payload->result[0]) {
            //IPS_LogMessage($payload->id, "ok");
            //TODO hier darf erst der Wert in IPS gesetzt werden....
        } else {
            IPS_LogMessage("YeelightColorBulb", "Unknown Notification received " . utf8_decode($data->Buffer));
        }
    }

    public function RequestAction($Ident, $Value)
    {
        //IPS_LogMessage("RequestAction ", utf8_decode($Ident) . " value: " . $Value);
        switch ($Ident) {
            case "power":
                $this->Power($Value);
                break;
            case "bright":
                $this->Brightness($Value);
                break;
            case "ct":
                $this->ColorTemp($Value);
                break;
            case "rgb":
                $this->RGB($Value);
                break;
            case "hue":
                $this->HUE($Value, $this->GetValue("sat"));
                break;
            case "sat":
                $this->HUE($this->GetValue("hue"), $Value);
                break;
            case "color_mode":
                $this->Mode($this->GetValue("color_mode"), $Value);
                break;
            default:
                throw new Exception("Invalid Ident: " . $Ident);
        }
    }

    public function Power($Value)
    {
        $this->buildAndSendCommand(self::POWER_CMD, "set_power", array($Value ? 'on' : 'off', 'smooth', 500));
    }

    public function Mode(int $mode)
    {
        $this->buildAndSendCommand(self::COLOR_MODE_CMD, "set_power", array('on', 'smooth', 500, $mode));
    }


    public function Brightness($Value)
    {
        $this->buildAndSendCommand(self::BRIGHTNESS_CMD, "set_bright", array($Value, 'smooth', 500));
    }

    public function ColorTemp($Value)
    {
        $this->buildAndSendCommand(self::COLOR_TEMP_CMD, "set_ct_abx", array($Value, 'smooth', 500));
    }

    public function RGB($Value)
    {
        $this->buildAndSendCommand(self::RGB_CMD, "set_rgb", array($Value, 'smooth', 500));
    }

    public function HUE($hue, $saturation)
    {
        $this->buildAndSendCommand(self::HUE_CMD, "set_hsv", array($hue, $saturation, 'smooth', 500));
    }

    private function buildAndSendCommand($id, $method, $params)
    {
        $Data = Array(
            'id' => $id,
            'method' => $method,
            'params' => $params
        );
        $payload = json_encode($Data);

        $this->SendDataToParent(json_encode(Array(
            "DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}",
            "Buffer" => $payload . "\r\n"
        )));
    }
}
?>