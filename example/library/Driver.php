<?php
/*------------------------------------------------------------------------------
MIT License

Copyright (c) 2022 Willian Donadelli

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
------------------------------------------------------------------------------*/

/*------------------------------------------------------------------------------
// para efetuar testes na biblioteca

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
------------------------------------------------------------------------------*/

class Driver {

/*----------------------------------------------------------------------------*/

	/* -- DADOS -- */
	private $VERSION = "v1.0.0"; /* registra versão da biblioteca */
	private $CONFIG;             /* registra os dados de configuração */
	private $STATUS;             /* regista o identificador do progresso */
	private $TRIGGERS = array(); /* registra os acionadores do objeto */
	private $EVENTS = array(     /* registra as fases da requisição */
		0 => "SESSION STARTED",
		1 => "AUTHENTICATION REQUIRED",
		2 => "AUTHENTICATION FAILED",
		3 => "SUCCESSFULLY AUTHENTICATED",
		4 => "PERMITTED ACCESS",
		5 => "ACCESS DENIED",
		6 => "PAGE NOT FOUND",
		7 => "SESSION CLOSED",
		8 => "SESSION EXPIRED",
		9 => "MODIFIED ROUTE"
	);

/*----------------------------------------------------------------------------*/
	public function __construct($config) {

		/* obter, definir e checar dados de configuração */
		$this->config($config);

		/* iniciar sessão */
		session_start();

		/* definir chaves de sessão */
		if (!array_key_exists("__DRIVER__", $_SESSION)) { /* chave principal */
			$_SESSION["__DRIVER__"] = array();
		}
		if (!array_key_exists("USER", $_SESSION["__DRIVER__"])) { /* dados do usuário (fixo) [mixed] */
			$_SESSION["__DRIVER__"]["USER"] = null;
		}
		if (!array_key_exists("TIME", $_SESSION["__DRIVER__"])) { /* momento do login (fixo) [string] */
			$_SESSION["__DRIVER__"]["TIME"] = null;
		}
		if (!array_key_exists("HASH", $_SESSION["__DRIVER__"])) { /* identificador do login (fixo) [string] */
			$_SESSION["__DRIVER__"]["HASH"] = null;
		}
		if (!array_key_exists("LOG", $_SESSION["__DRIVER__"])) { /* histórico de ações (array) */
			$_SESSION["__DRIVER__"]["LOG"] = array();
		}

		/* definir status */
		$this->STATUS = 0;
		return;
	}


/*----------------------------------------------------------------------------*/
	private function config($config) {
		/* obtem e define dados da configuração conforme argumento do construtor (array ou arquivo JSON) */

		/* se for um arquivo, obter dados */
		if ($this->isFile($config)) {

			/* 1) tentar ler o conteúdo do arquivo */
			$content = file_get_contents($config);
			if ($content === false) {
				trigger_error("Driver: Error reading configuration file.");
				exit;
			}

			/* 2) tentar decodifica o JSON para array */
			$array = json_decode($content, true);
			if ($array === null) {
				trigger_error("Driver: Error in JSON file structure.");
				exit;
			}

			/* 3) checar o dado primário do JSON é um array */
			if (gettype($array) !== "array") {
				trigger_error("Driver: Inadequate configuration data.");
				exit;
			}

			/* 4) definir CONFIG */
			$this->CONFIG = $array;

		} elseif (gettype($config) === "array") {
			/* definir direto se for um array */
			$this->CONFIG = $config;

		} else {
			/* no caso de falhar com o arquivo ou array */
			trigger_error("Driver: inappropriate argument.");
			exit;
		}

		/* checar dados de CONFIG */
		$this->check();

		return;
	}


/*----------------------------------------------------------------------------*/
	private function check() {
		/* checa os dados e retorna um erro se irreparável */

		/* HOME: página inicial (obrigatório) [string:file] */
		if (!array_key_exists("HOME", $this->CONFIG)) {
			trigger_error("CONFIG[HOME]: Information not provided.");
			exit;
		}
		if (!$this->isFile($this->CONFIG["HOME"])) {
			trigger_error("CONFIG[HOME]: No file found.");
			exit;
		}


		/* ID: lista de páginas/arquivos (facultativo) [array] */
		if (!array_key_exists("ID", $this->CONFIG)) {
			$this->CONFIG["ID"] = array();
		}
		foreach($this->CONFIG["ID"] as $id => $file) {
			if (in_array($id, array("HOME", "EXIT"))) {
				trigger_error("CONFIG[ID][{$id}]: Inappropriate identifier.");
				exit;
			}
			if (!$this->isFile($file)) {
				trigger_error("CONFIG[ID][{$id}]: No file found.");
				exit;
			}
		}


		/* LOG: informações sobre autenticação (facultativo) [array] */
		if (!array_key_exists("LOG", $this->CONFIG)) {
			return $this->CONFIG["LOG"] = null;
		}
		if (gettype($this->CONFIG["LOG"]) !== "array") {
			trigger_error("CONFIG[LOG]: Inappropriate information.");
			exit;
		}


		/* LOG.GATEWAY: página de credenciais para autenticação (obrigatório) [string:file] */
		if (!array_key_exists("GATEWAY", $this->CONFIG["LOG"])) {
			trigger_error("CONFIG[LOG][GATEWAY]: Information not provided.");
			exit;
		}
		if (!$this->isFile($this->CONFIG["LOG"]["GATEWAY"])) {
			trigger_error("CONFIG[LOG][GATEWAY]: No file found.");
			exit;
		}


		/* LOG.DATA: lista contendo nome dos formulários de autenticação (obrigatório) [array]*/
		if (!array_key_exists("DATA", $this->CONFIG["LOG"])) {
			trigger_error("CONFIG[LOG][DATA]: Information not provided.");
			exit;
		}
		if (gettype($this->CONFIG["LOG"]["DATA"]) !== "array") {
			trigger_error("CONFIG[LOG][DATA]: Inappropriate information.");
			exit;
		}
		if (count($this->CONFIG["LOG"]["DATA"]) === 0) {
			trigger_error("CONFIG[LOG][DATA]: Insufficient data.");
			exit;
		}


		/* LOG.LOGIN: nome da função que checará as credenciais (obrigatório) [string:function] */
		if (!array_key_exists("LOGIN", $this->CONFIG["LOG"])) {
			trigger_error("CONFIG[LOG][LOGIN]: Information not provided.");
			exit;
		}
		if (gettype($this->CONFIG["LOG"]["LOGIN"]) !== "string") {
			trigger_error("CONFIG[LOG][LOGIN]: Inappropriate information.");
			exit;
		}
		if (!is_callable($this->CONFIG["LOG"]["LOGIN"])) {
			trigger_error("CONFIG[LOG][LOGIN]: Function/method not found.");
			exit;
		}


		/* LOG.LOAD: nome da função que checará o acesso à página (facultativo) [string:function] */
		if (array_key_exists("LOAD", $this->CONFIG["LOG"])) {
			if (gettype($this->CONFIG["LOG"]["LOAD"]) !== "string") {
				trigger_error("CONFIG[LOG][LOAD]: Inappropriate information.");
				exit;
			}
			if (!is_callable($this->CONFIG["LOG"]["LOAD"])) {
				trigger_error("CONFIG[LOG][LOAD]: Function/method not found.");
				exit;
			}
		} else {
			$this->CONFIG["LOG"]["LOAD"] = null;
		}


		/* LOG.TIME: tempo, em segundos, entre páginas (facultativo) [integer] */
		if (array_key_exists("TIME", $this->CONFIG["LOG"])) {
			if (gettype($this->CONFIG["LOG"]["TIME"]) !== "integer") {
				trigger_error("CONFIG[LOG][TIME]: Inappropriate information.");
				exit;
			}
			if ($this->CONFIG["LOG"]["TIME"] < 1) {
				trigger_error("CONFIG[LOG][TIME]: Inappropriate time interval.");
				exit;
			}
		} else {
			$this->CONFIG["LOG"]["TIME"] = null;
		}

		return;
	}

/*----------------------------------------------------------------------------*/
	private function time($format = false) {
		/* retorna a hora no formato YYYY-MM-DD HH:MM:SS ou em segundos */
		$time  = new DateTime();
		return $format === true ? $time->format("Y-m-d H:i:s") : (int) $time->format("U");
	}

/*----------------------------------------------------------------------------*/
	private function isFile($path) {
		/* verifica se o argumento é um caminho de arquivo válido (boolean) */
		if (gettype($path) !== "string") {return false;}
		if (!is_file($path)) {return false;}
		return true;
	}

/*----------------------------------------------------------------------------*/
	private function hash() {
		/* define o identificador da sessão a partir dos dados do usuário */

		/* checando se precisa de autenticação e os valores de sessão */
		if ($this->CONFIG["LOG"] === null)            {return null;}
		if ($_SESSION["__DRIVER__"]["USER"] === null) {return null;}
		if ($_SESSION["__DRIVER__"]["TIME"] === null) {return null;}

		/* definindo dados do hash */
		$hash = array(
			"USER" => $_SESSION["__DRIVER__"]["USER"],
			"TIME" => $_SESSION["__DRIVER__"]["TIME"]
		);

		return md5(json_encode($hash));
	}

/*----------------------------------------------------------------------------*/
	private function log() {
		/* informa se o usuário está autenticado (boolean) */

		/* conferir e comparar hash com os dados da sessão */
		$hash = $this->hash();

		if ($hash === null) {return false;}

		return $hash === $_SESSION["__DRIVER__"]["HASH"] ? true : false;
	}

/*----------------------------------------------------------------------------*/
	private function login() {
		/* informa se o usuário está pedindo autenticação (boolean) */

		/* se não exigir altenticação, não tem o que verificar */
		if ($this->CONFIG["LOG"] === null) {return false;}

		/* se já autenticado, não há como solicitar autenticação */
		if ($this->log()) {return false;}

		/* checar se a última página foi de login */
		if ($this->lastRequest("PAGE") !== $this->CONFIG["LOG"]["GATEWAY"]) {return false;}

		/* checar se os dados do POST conferem com os da autenticação (LOG.DATA) */
		if (count($_POST) !== count($this->CONFIG["LOG"]["DATA"])) {return false;}

		foreach ($this->CONFIG["LOG"]["DATA"] as $value) { /* checar nomes dos formulários */
			if (!array_key_exists($value, $_POST)) {return false;}
		}

		return true;
	}

/*----------------------------------------------------------------------------*/
	private function logout() {
		/* encerra a sessão */
		if (isset($_SESSION)) {
			session_unset();
			session_destroy();
		}
		return;
	}

/*----------------------------------------------------------------------------*/
	private function register() {
		/* registra a autenticação e devolve o resultado (boolean) */

		/* chamando a função para checar credenciais e obter dados do usuário em caso de sucesso */
		$user = call_user_func($this->CONFIG["LOG"]["LOGIN"], $_POST);

		/* conferindo se a autenticação falhou */
		if ($user === null) {return false;}

		/* em caso de sucesso, definir dados da sessão */
		$_SESSION["__DRIVER__"]["USER"] = $user;
		$_SESSION["__DRIVER__"]["TIME"] = $this->time(true);
		$_SESSION["__DRIVER__"]["HASH"] = $this->hash();

		return true;
	}

/*----------------------------------------------------------------------------*/
	private function id() {
		/* retorna o valor do id */
		return array_key_exists("id", $_GET) ? $_GET["id"] : null;
	}

/*----------------------------------------------------------------------------*/
	private function lastRequest($id = null) {
		/* retorna os dados da última interação */
		$last = count($_SESSION["__DRIVER__"]["LOG"]) - 1;
		if ($last < 0) {return null;}
		$item = $_SESSION["__DRIVER__"]["LOG"][$last];
		if ($id === null || !array_key_exists($id, $item)) {return $item;}
		return $item[$id];
	}

/*----------------------------------------------------------------------------*/
	private function freeAccess() {
		/* retorna a rota sem autenticação */

		$id = $this->id();

		/* rota indefinida, HOME ou EXIT: HOME */
		if (in_array($id, array(null, "HOME", "EXIT"))) {
			$this->STATUS = 4;
			return $this->CONFIG["HOME"];
		}

		/* rota incorreta: HOME */
		if (!array_key_exists($id, $this->CONFIG["ID"])) {
			$this->STATUS = 6;
			return $this->CONFIG["HOME"];
		}

		/* rota correta: rota */
		$this->STATUS = 4;
		return $this->CONFIG["ID"][$id];
	}

/*----------------------------------------------------------------------------*/
	private function restrictedAccess() {
		/* define a rota com autenticação */

		/* usuário tentando autenticar */
		if ($this->login()) {
			if ($this->register()) { /* sucesso: HOME */
				$this->STATUS = 3;
				return $this->CONFIG["HOME"];
			} else { /* falha: GATEWAY */
				$this->STATUS = 2;
				return $this->CONFIG["LOG"]["GATEWAY"];
			}
		}

		/* usuário não autenticado: GATEWAY */
		if (!$this->log()) {
			$this->STATUS = 1;
			return $this->CONFIG["LOG"]["GATEWAY"];
		}

		/* usuário autenticado: obter id da rota */
		$id = $this->id();

		/* usuário encerando sessão: GATEWAY */
		if ($id === "EXIT") {
				$this->STATUS = 7;
				return $this->CONFIG["LOG"]["GATEWAY"];
		}

		/* tempo da sessão expirado: GATEWAY */
		$time = $this->CONFIG["LOG"]["TIME"];
		$past = $this->lastRequest("TIME");
		if ($time !== null && $past !== null) {
			if ($this->time() > ($past + $time)) { /* prazo expirado: GATEWAY */
				$this->STATUS = 8;
				return $this->CONFIG["LOG"]["GATEWAY"];
			}
		}

		/* rota não definida ou HOME: HOME */
		if (in_array($id, array(null, "HOME"))) {
			$this->STATUS = 4;
			return $this->CONFIG["HOME"];
		}

		/* rota inexistente: HOME */
		if (!array_key_exists($id, $this->CONFIG["ID"])) {
			$this->STATUS = 6;
			return $this->CONFIG["HOME"];
		}

		/* rota existente: obter caminho */
		$path = $this->CONFIG["ID"][$id];

		/* acesso não permitido: HOME */
		$load = $this->CONFIG["LOG"]["LOAD"];
		if ($load !== null) {
			$user  = $_SESSION["__DRIVER__"]["USER"];
			$check = call_user_func($load, $user, $path);
			if ($check !== true) {
				$this->STATUS = 5;
				return $this->CONFIG["HOME"];
			}
		}

		/* rota permitida: rota */
		$this->STATUS = 4;
		return $this->CONFIG["ID"][$id];
	}

/*----------------------------------------------------------------------------*/
	public function path() {
		/* avalia e conduz a rota a ser exibida */
		$log = $this->CONFIG["LOG"];
		$path = $log === null ? $this->freeAccess() : $this->restrictedAccess();

		/* FIXME checar eventos e ver quando chamar logout */

		/* definir registros */
		if (gettype($_SESSION["__DRIVER__"]["LOG"]) !== "array") {
			$_SESSION["__DRIVER__"]["LOG"] = array();
		}

		$_SESSION["__DRIVER__"]["LOG"][] = array(
			"PAGE"   => $path,
			"TIME"   => $this->time(),
			"STATUS" => $this->STATUS
		);


		/* retornar rota */
		return $path;
	}

/*----------------------------------------------------------------------------*/
	public function status($text = false) {
		/* retorna o valor do progresso da requisição */
		return $text === true ? $this->EVENTS[$this->STATUS] : $this->STATUS;
	}

/*----------------------------------------------------------------------------*/
	public function version() {
		/* retorna a versão da biblioteca */
		return $this->VERSION;
	}

/*----------------------------------------------------------------------------*/
	public function debug($print = false) {
		/* retorna ou imprime os dados de sessão e de configuração */
		$status = $this->status(true);

		$data = array(
			"CONFIG"   => $this->CONFIG,
			"JSON"     => json_encode($this->CONFIG),
			"STATUS"   => "{$this->STATUS}: {$status}",
			"_SESSION" => $_SESSION
		);

		if ($print === true) {
			echo "<pre>";
			print_r($data);
			echo "</pre>";
		}
		return $data;
	}

}
?>
