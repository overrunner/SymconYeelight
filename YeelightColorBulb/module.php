<?
// Klassendefinition
class YeelightColorBulb extends IPSModule {

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
    }


    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        $this->RegisterVariableBoolean("power", "Power", "~Switch", 0);
        $this->RegisterVariableInteger("bright", "Brightness", "~Intensity.100", 1);
        $this->RegisterVariableInteger("ct", "Color Temperature", "~Intensity.65535", 2);
        $this->RegisterVariableString("rgb", "Color", "~String", 3);
        $this->RegisterVariableString("hue", "Hue", "~String", 4);
        $this->RegisterVariableInteger("sat", "Saturation", "~Intensity.100", 5);
        $this->RegisterVariableInteger("color_mode", "Color Mode", "", 6);

        $this->GetConfigurationForParent();

        $this->EnableAction("power");
        $this->EnableAction("bright");
    }

    public function GetConfigurationForParent()
    {
        $host = $this->ReadPropertyString("ipadress");
        $port = 55443;
        return "{\"Host\": \"$host\", \"Port\": \"$port\"}";
    }


    // Lese alle Konfigurationsdaten aus
    public function readStatesFromDevice() {
        $this->buildAndSendCommand('444', 'get_prop', array('power', 'bright', 'ct', 'rgb', 'hue', 'sat', 'color_mode'));
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $payload = json_decode($data->Buffer);
        //27/12/2017 16:06:33 | Receive | {"id":1, "result":["off","27","2945","255","359","100","2"]}
        // this is our Datafetch from Configuration GUI, which has a fixed sorting of values
        if (isset($payload->id) && '444' == $payload->id) {
            SetValueBoolean(IPS_GetObjectIDByIdent("power", $this->InstanceID), 'on' == $payload->result[0] ? TRUE : FALSE);
            SetValueInteger(IPS_GetObjectIDByIdent("bright", $this->InstanceID), $payload->result[1]);
            SetValueInteger(IPS_GetObjectIDByIdent("ct", $this->InstanceID), $payload->result[2]);
            SetValueString(IPS_GetObjectIDByIdent("rgb", $this->InstanceID), $payload->result[3]);
            SetValueString(IPS_GetObjectIDByIdent("hue", $this->InstanceID), $payload->result[4]);
            SetValueInteger(IPS_GetObjectIDByIdent("sat", $this->InstanceID), $payload->result[5]);
            SetValueInteger(IPS_GetObjectIDByIdent("color_mode", $this->InstanceID), $payload->result[6]);
            IPS_LogMessage("Reciever", "done");
            return;
        }

        //IPS_LogMessage("Receiver", utf8_decode($data->Buffer));
        //27/12/2017 17:40:01 | Receiver | {"method":"props","params":{"power":"off"}}
        if (isset($payload->method) && 'props' == $payload->method) {
            foreach ($payload->params as $key => $val) {
                if ('power' == $key) {
                    SetValueBoolean(IPS_GetObjectIDByIdent("power", $this->InstanceID), 'on' == $val ? TRUE : FALSE);
                } else if ('bright' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("bright", $this->InstanceID), $val);
                } else if ('ct' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("ct", $this->InstanceID), $val);
                } else if ('rgb' == $key) {
                    SetValueString(IPS_GetObjectIDByIdent("rgb", $this->InstanceID), $val);
                } else if ('hue' == $key) {
                    SetValueString(IPS_GetObjectIDByIdent("hue", $this->InstanceID), $val);
                } else if ('sat' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("sat", $this->InstanceID), $val);
                } else if ('color_mode' == $key) {
                    SetValueInteger(IPS_GetObjectIDByIdent("color_mode", $this->InstanceID), $val);
                }
            }
        } else {
            IPS_LogMessage("YeelightColorBulb", "Unknown Notification received " . utf8_decode($data->Buffer));
        }
    }

    public function RequestAction($Ident, $Value)
    {
        IPS_LogMessage("RequestAction ", utf8_decode($Ident) . " value: " . $Value);
        switch ($Ident) {
            case "power":
                $this->Power($Value);
                break;
            case "bright":
                $this->Brightness($Value);
                break;
            default:
                throw new Exception("Invalid Ident: " . $Ident);
        }
    }

    public function Power($Value)
    {
        $this->buildAndSendCommand(10, "set_power", array($Value ? 'on' : 'off', 'smooth', 500));
    }

    public function Brightness($Value)
    {
        $this->buildAndSendCommand('20', "set_bright", array($Value));
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