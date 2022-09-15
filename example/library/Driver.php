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
	private $PROGRESS = array(   /* registra as fases da requisição */
		0 => "SESSION STARTED", /* (antes de chamar url) */
		1 => "AUTHENTICATION REQUIRED",
		2 => "AUTHENTICATION FAILED",
		3 => "SUCCESSFULLY AUTHENTICATED",
		4 => "PERMITTED ACCESS",
		5 => "ACCESS DENIED",
		6 => "PAGE NOT FOUND",
		7 => "SESSION CLOSED",
		8 => "SESSION EXPIRED"
	);
	private $SESSION = array(    /* registra chaves da sessão (tipo, valor inicial, participa do hash) */
		"USER",   /* dados do usuário (fixo) */
		"TIME",   /* hora do login (fixo) */
		"HASH",   /* identificador do login (fixo) */
		"ACCESS", /* momento de cada interação (variável) */
		"PAGE",   /* última página acessada (variável) */
		"STATUS"  /* último status (variável) */
	);


/*----------------------------------------------------------------------------*/
	public function __construct($config) {
		/* obter dados de configuração */
		$this->config($config);

		/* iniciar sessão */
		start_session();

		/* definir dados iniciais de sessão, se necessário */
		if (!array_key_exists("__DRIVER__", $_SESSION)) {

			$_SESSION["__DRIVER__"] = array();

			foreach ($this->SESSION as $id) {
				if (!array_key_exists($id, $_SESSION["__DRIVER__"])) {
					$_SESSION["__DRIVER__"][] = null;
				}
			}

		}

		/* definir status */
		$this->STATUS = 0;
		return;
	}


/*----------------------------------------------------------------------------*/
	private function config($config) {
		/* checa e retorna config, se array ou arquivo JSON */

		/* se for um arquivo, obter dados */
		if ($this->isFile($config))
		{

			/* ler o conteúdo do arquivo: conseguiu? */
			$content = file_get_contents($config);
			if ($content === false) {
				return trigger_error("Driver: Error reading configuration file.");
			}

			/* decodificando o JSON para array: funcionou? */
			$array = json_decode($content, true);
			if ($array === null) {
				return trigger_error("Driver: Error in JSON file structure.");
			}

			/* o JSON é um array ? */
			if (gettype($array) !== "array") {
				return trigger_error("Driver: Inadequate configuration data.");
			}

			$this->CONFIG = $config;
		}
		elseif (gettype($config) === "array")
		{
			$this->CONFIG = $config;
		}
		else {
			return trigger_error("Driver: inappropriate argument.");
		}

		return $this->check();
	}


/*----------------------------------------------------------------------------*/
	public function check() {
		/* checa os dados e retorna um erro se irreparável */

		$config = $;

		/* HOME: página inicial (obrigatório) */
		if (!array_key_exists("HOME", $this->CONFIG)) {
			return trigger_error("CONFIG[HOME]: Information not provided.");
		}
		if (!$this->isFile($this->CONFIG))
			return trigger_error("CONFIG[HOME]: No file found.");
		}

		/* ID: lista de páginas/arquivos (facultativo) */
		if (!array_key_exists("ID", $this->CONFIG)) {
			$this->CONFIG["ID"] = array();
		}
		foreach($this->CONFIG["ID"] as $id => $file) {
			if (in_array($id, array("HOME", "EXIT")) {
				return trigger_error("CONFIG[ID][{$id}]: Inappropriate identifier.");
			}
			if (!$this->isFile($file))
				return trigger_error("CONFIG[ID][{$id}]: No file found.");
			}
		}

		/* LOG: informações sobre autenticação (facultativo) */
		if (!array_key_exists("LOG", $this->CONFIG)) {
			$this->CONFIG["LOG"] = null;
			return;
		}

		/* LOG.GATEWAY: página de credenciais para autenticação (obrigatório) */
		if (!array_key_exists("GATEWAY", $this->CONFIG["LOG"])) {
			return trigger_error("CONFIG[LOG][GATEWAY]: Information not provided.");
		}
		if (!$this->isFile($this->CONFIG["LOG"]["GATEWAY"]))
			return trigger_error("CONFIG[LOG][GATEWAY]: No file found.");
		}

		/* LOG.DATA: lista contendo nome dos formulários de autenticação (obrigatório) */
		if (!array_key_exists("DATA", $this->CONFIG["LOG"])) {
			return trigger_error("CONFIG[LOG][DATA]: Information not provided.");
		}
		if (gettype($this->CONFIG["LOG"]["DATA"]) !== "array") {
			return trigger_error("CONFIG[LOG][DATA]: Inappropriate information.");
		}
		if (count($this->CONFIG["LOG"]["DATA"]) === 0) {
			return trigger_error("CONFIG[LOG][DATA]: Insufficient data.");
		}

		/* LOG.LOGIN: nome da função que checará as credenciais (obrigatório) */
		if (!array_key_exists("LOGIN", $this->CONFIG["LOG"])) {
			return trigger_error("CONFIG[LOG][LOGIN]: Information not provided.");
		}
		if (gettype($this->CONFIG["LOG"]["LOGIN"]) !== "string") {
			return trigger_error("CONFIG[LOG][LOGIN]: Inappropriate information.");
		}
		if (!is_callable($this->CONFIG["LOG"]["LOGIN"])) {
			return trigger_error("CONFIG[LOG][LOGIN]: Function/method not found.");
		}

		/* LOG.LOAD: nome da função que checará o acesso à página (facultativo) */
		if (array_key_exists("LOAD", $this->CONFIG["LOG"])) {
			if (gettype($this->CONFIG["LOG"]["LOAD"]) !== "string") {
				return trigger_error("CONFIG[LOG][LOAD]: Inappropriate information.");
			}
			if (!is_callable($this->CONFIG["LOG"]["LOAD"])) {
				return trigger_error("CONFIG[LOG][LOAD]: Function/method not found.");
			}
		} else {
			$this->CONFIG["LOG"]["LOAD"] = null;
		}

		/* LOG.TIME: tempo, em segundos, entre páginas (facultativo) */
		if (array_key_exists("TIME", $this->CONFIG["LOG"])) {
			if (gettype($this->CONFIG["LOG"]["TIME"]) !== "integer") {
				return trigger_error("CONFIG[LOG][TIME]: Inappropriate information.");
			}
			if ($this->CONFIG["LOG"]["TIME"] < 1) {
				return trigger_error("CONFIG[LOG][TIME]: Inappropriate time interval.");
			}
		} else {
			$this->CONFIG["LOG"]["TIME"] = null;
		}

		return;
	}

/*----------------------------------------------------------------------------*/
	private function time($format = false) {
		/* retorna a hora no formato YYYY-MM-DD HH:MM:SS */
		$time  = new DateTime();
		if ($format === true) {return $time->format("Y-m-d H:i:s");}
		return (int) $time->format("U");
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
		/* informa se o usuário pediu autenticação (boolean) */

		/* se não exigir altenticação, não tem o que verificar */
		if ($this->CONFIG["LOG"] === null) {return false;}

		/* se já autenticado, não há como solicitar autenticação */
		if ($this->log()) {return false;}

		/* checar se a última página foi de login */
		if ($_SESSION["__DRIVER__"]["PAGE"] !== $this->CONFIG["LOG"]["GATEWAY"]) {
			return false;
		}

		/* checar se os dados do POST conferem com os da autenticação (LOG.DATA) */
		if (count($_POST) !== count($this->CONFIG["LOG"]["DATA"])) {return false;}
		foreach ($this->CONFIG["LOG"]["DATA"] as $value) {
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
	private function freeAccess() {
		/* retorna a rota sem autenticação */

		/* sem id informado: HOME */
		if (!array_key_exists("id", $_GET)) {
			$this->STATUS = 4;
			return $this->CONFIG["HOME"];
		}
		/* id=HOME ou id=EXIT: HOME */
		if (in_array($_GET["id"], array("HOME", "EXIT"))) {
			$this->STATUS = 4;
			return $this->CONFIG["HOME"];
		}
		/* id não existe: HOME */
		if (!array_key_exists($_GET["id"], $this->CONFIG["ID"])) {
			$this->STATUS = 6;
			return $this->CONFIG["HOME"];
		}
		/* caso contrário: ID */
		$this->STATUS = 4
		return $this->CONFIG["ID"][$_GET["id"]];
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
				$this->logout();
				$this->STATUS = 2;
				return $this->CONFIG["LOG"]["GATEWAY"];
			}
		}

		/* usuário não autenticado: GATEWAY */
			if (!$this->log()) {
				$this->logout();
				$this->STATUS = 1;
				return $this->CONFIG["LOG"]["GATEWAY"];
			}

			/* usuário logado: obter rota FIXME */



			/* usuário encerando sessão: GATEWAY */
			if ($id === "EXIT" || $id === "LOGOUT") {
					$this->logout();
					$this->STATUS = 7;
					return return $this->CONFIG["LOG"]["GATEWAY"];
			}

			/* 1.4: usuário logado: checar tempo entre requisições, se estabelecido */
			if ($timeout !== null) {
				$time = $this->time();
				if ($time > ($_SESSION["__INIT__"] + $timeout)) { /* prazo expirado: logout */
					$this->logout();
					$this->STATUS = 8;
					return $logout;
				} else {
					$_SESSION["__INIT__"] = $time;
				}


	}

/*----------------------------------------------------------------------------*/
	public function path() {
		/* devolve o caminho do arquivo a ser exibida */

		$id      = array_key_exists("id", $_GET) ? $_GET["id"] : "HOME";
		$exists  = array_key_exists($id, $target);
		$path    = $exists ? $target[$id] : $home;







		$gateway = $this->CONFIG["GATEWAY"];
		$target  = $this->CONFIG["TARGET"];
		$home    = $target["HOME"];
		$login   = $target["LOGIN"];
		$logout  = $target["LOGOUT"];
		$log     = $this->CONFIG["LOG"];
		$auth    = $this->CONFIG["AUTH"];
		$check   = $this->CONFIG["CHECK"];
		$timeout = $this->CONFIG["TIMEOUT"];
		$id      = array_key_exists("id", $_GET) ? $_GET["id"] : "HOME";
		$exists  = array_key_exists($id, $target);
		$path    = $exists ? $target[$id] : $home;

		/* 1) requisição com autenticação */
		if ($gateway) {

			/* 1.1) usuario tentando autenticar: home || login */
			if ($this->login()) {
				if ($this->register()) { /* sucesso: home */
					$this->STATUS = 3;
					return $home;
				} else { /* falha: login */
					$this->logout();
					$this->STATUS = 2;
					return $login;
				}
			}

			/* 1.2) usuário não autenticado: login */
			if (!$this->log()) {
				$this->logout();
				$this->STATUS = 1;
				return $login;
			}

			/* 1.3: usuário encerando sessão: logout */
			if ($id === "LOGIN" || $id === "LOGOUT") {
					$this->logout();
					$this->STATUS = 7;
					return $logout;
			}

			/* 1.4: usuário logado: checar tempo entre requisições, se estabelecido */
			if ($timeout !== null) {
				$time = $this->time();
				if ($time > ($_SESSION["__INIT__"] + $timeout)) { /* prazo expirado: logout */
					$this->logout();
					$this->STATUS = 8;
					return $logout;
				} else {
					$_SESSION["__INIT__"] = $time;
				}
			}

		}

		/* 2) verificar rota */
		/* 2.1: HOME, LOGIN e LOGOUT não precisa verificar acesso */
		if (in_array($id, array("HOME", "LOGIN", "LOGOUT"))) {
			$this->STATUS = 4;
			return $path;
		}

		/* 2.2: checando acesso, se for o caso de autenticação requerida */
		if ($gateway && $check !== null) {

			/* chamando função verificadora */
			$check = call_user_func($check, $_SESSION["__USER__"], $id, $path);

			if ($check) { /* acesso permitido: requisição */
				$this->STATUS = 4;
				return $path;
			} else { /* acesso negado: home */
				$this->STATUS = 5;
				return $home;
			}
		}

		/* 2.3: direcionar para a página */
		$this->STATUS = $exists ? 4 : 6;
		return $path;
	}

/*----------------------------------------------------------------------------*/
	public function status($text = false) {
		/* retorna o valor do progresso da requisição */
		return $text === true ? $this->PROGRESS[$this->STATUS] : $this->STATUS;
	}

/*----------------------------------------------------------------------------*/
	public function version() {
		/* retorna a versão da biblioteca */
		return $this->VERSION;
	}

/*----------------------------------------------------------------------------*/
	public function debug($print = false) {
		/* retorna ou imprime os dados de sessão e de configuração */
		$data = array(
			"CONFIG"   => $this->CONFIG,
			"JSON"     => json_encode($this->CONFIG),
			"STATUS"   => $this->STATUS,
			"PROGRESS" => $this->PROGRESS,
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
