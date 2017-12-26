<?
// Klassendefinition
class YeelightColorBulb extends IPSModule {

    // Der Konstruktor des Moduls
    // Überschreibt den Standard Kontruktor von IPS
    public function __construct($InstanceID) {
        // Diese Zeile nicht löschen
        parent::__construct($InstanceID);

        // Selbsterstellter Code
    }

    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        parent::Create();

        $this->RegisterPropertyString("ipadress", "");
        $this->RegisterPropertyInteger("intervall", "30");

    }

    // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        $this->RegisterVariableString("name", "Name", "~String",1);
        $this->RegisterVariableString("power", "Power", "~Switch",2);
        $this->RegisterVariableInteger("dim", "Dimmer", "~Intensity.100",3);

 //       $this->RegisterTimer('ReadData', $this->readStatesFromDevice("intervall") * 1000, 'YCB_readStatesFromDevice($id)');
        $pid = $this->RequireParent("61791C2F-DE68-4CB7-9D25-BAD5803F9FEE");
        IPS_SetName($pid, __CLASS__ . " Socket");

    }

    // Lese alle Konfigurationsdaten aus
    public function readStatesFromDevice() {
        $ip = $this->ReadPropertyString("ipadress");
        $url = "http://".$ip.":8080/api/v2/device";
        $response = LM_callapi($this->InstanceID, $url, array(), "GET");
        $data = json_decode($response);
        if ($data->display->brightness_mode == "auto") { $mode=true; } else { $mode=false; };
        SetValue(IPS_GetObjectIDByName("Volume", $this->InstanceID), $data->audio->volume);
        SetValue(IPS_GetObjectIDByName("Helligkeit", $this->InstanceID),$data->display->brightness);
        SetValueBoolean(IPS_GetObjectIDByName("Helligkeit Auto Modus", $this->InstanceID),$mode);
        SetValueBoolean(IPS_GetObjectIDByName("Bluetooth", $this->InstanceID),$data->bluetooth->active);
        SetValue(IPS_GetObjectIDByName("Bluetooth Name", $this->InstanceID),$data->bluetooth->name);
        SetValue(IPS_GetObjectIDByName("Name", $this->InstanceID),$data->name);
        SetValue(IPS_GetObjectIDByName("OS Version", $this->InstanceID),$data->os_version);
        SetValue(IPS_GetObjectIDByName("SSID", $this->InstanceID),$data->wifi->essid);
        SetValue(IPS_GetObjectIDByName("WLan Empfang", $this->InstanceID),$data->wifi->strength);
        if (file_exists(IPS_GetKernelDir()."/scripts/LM_setdisplay.php") == false) {
            copy(IPS_GetKernelDir()."/modules/Symcon-LaMetric/LaMetric/setdisplay.php", IPS_GetKernelDir()."/scripts/LM_setdisplay.php");
            copy(IPS_GetKernelDir()."/modules/Symcon-LaMetric/LaMetric/setbluetooth.php", IPS_GetKernelDir()."/scripts/LM_setbluetooth.php");
            copy(IPS_GetKernelDir()."/modules/Symcon-LaMetric/LaMetric/setvolume.php", IPS_GetKernelDir()."/scripts/LM_setvolume.php");
        }
        return $data;
    }




    function SendToYeelight($method, $params) {
        $Data = Array(
            'id' => 1,
            'method' => $method,
            'params' => $params
        );
        $Line = json_encode($Data);

        $ip = $this->ReadPropertyString("ipadress");

    }
}
?>