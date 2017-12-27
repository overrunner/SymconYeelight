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
        $this->RegisterPropertyInteger("intervall", "30");

        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}"); // CLIENT SOCKET



        //       $this->RegisterTimer('ReadData', $this->readStatesFromDevice("intervall") * 1000, 'YCB_readStatesFromDevice($id)');

    }


    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        $this->RegisterVariableString("name", "Name", "~String",1);
        $this->RegisterVariableBoolean("power", "Power", "~Switch",2);
        $this->RegisterVariableInteger("brightness", "Brightness", "~Intensity.100",3);

        $this->GetConfigurationForParent();


    }

    public function GetConfigurationForParent()
    {
        $host = $this->ReadPropertyString("ipadress");
        $port = 55443;

        return "{\"Host\": \"$host\", \"Port\": \"$port\"}";
    }


    // Lese alle Konfigurationsdaten aus
    public function readStatesFromDevice() {

        $commandStr = $this->buildCommandString('get_prop', array('name', 'power', 'bright'));
        $result = $this->SendDataToParent($commandStr."\r\n");
        SetValue(IPS_GetObjectIDByName("name", $this->InstanceID), $result);
    }




    private function buildCommandString($method, $params) {
        $Data = Array(
            'id' => 1,
            'method' => $method,
            'params' => $params
        );
        return json_encode($Data);
    }
}
?>