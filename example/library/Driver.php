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
		"__USER__" => array("TYPE" => "array",   "INITIAL" => null, "HASH" => true),
		"__TIME__" => array("TYPE" => "string",  "INITIAL" => null, "HASH" => true),
		"__HASH__" => array("TYPE" => "string",  "INITIAL" => null, "HASH" => false),
		"__INIT__" => array("TYPE" => "integer", "INITIAL" => null, "HASH" => false)
	);

/*----------------------------------------------------------------------------*/
	public function __construct($config) {
		echo "Construtor:<br>";
		$this->session();
		$this->CONFIG = $this->config($config);
		$this->STATUS = 0;
		return;
	}

/*----------------------------------------------------------------------------*/
	private function session() {
		/* inicializa sessão e define/confere/retorna a compatibilidade dos dados */

		if (!isset($_SESSION)) {session_start();}

		$data = true;

		echo $_SESSION["__HASH__"];//FIXME por quê?

		foreach ($this->SESSION as $key => $value) {
			/* obtendo dados */
			$check = array_key_exists($key, $_SESSION);
			$type  = $check ? gettype($_SESSION[$key]) : null;
			$match = $value["TYPE"] === $type ? true : false;
			/* valor inadequado ou inexistente */
			if (!$check || !$match) {
				$_SESSION[$key] = $value["INITIAL"];
				$data = false;
			}
		}

		return $data;
	}

/*----------------------------------------------------------------------------*/
	private function config($config) {
		/* checa e retorna config, se array ou arquivo JSON */

		/* checando o tipo do argumento: array ou string */
		$type = gettype($config);
		if ($type !== "array" && $type !== "string") {
			return trigger_error("Driver: inappropriate argument.");
		}

		/* se for array: retornar */
		if ($type === "array") {
			return $config;
		}

		/* se for um string: checar se é um arquivo */
		if (!is_file($config)) {
			return trigger_error("Driver: Configuration file not found.");
		}

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

		/* se tudo der certo: retornar JSON */
		return $array;
	}

/*----------------------------------------------------------------------------*/
	public function check() {
		/* checa os dados e retorna um erro se irreparável */

		/* GATEWAY: informa se o sistema será uma porta de entrada (facultativo) */
		$check = array_key_exists("GATEWAY", $this->CONFIG);
		$value = $check ? $this->CONFIG["GATEWAY"] : null;
		$this->CONFIG["GATEWAY"] = $value === true ? true : false;


		/* TARGET: define a lista de arquivos e identificadores (obrigatório) */
		$check = array_key_exists("TARGET", $this->CONFIG);
		$value = $check ? $this->CONFIG["TARGET"] : null;
		$type  = gettype($value);

		if ($type !== "array") { /* checando o tipo */
			return trigger_error("Driver[TARGET]: Information not provided or inappropriate");
		}

		foreach($value as $id => $path) { /* checando a lista */
			if (gettype($path) !== "string") { /* é uma string? */
				return trigger_error("Driver[TARGET][{$id}]: The value must be a string.");
			}
			if (!is_file($path)) { /* é um caminho válido? */
				return trigger_error("Driver[TARGET][{$id}]: No file found.");
			}
		}


		/* TARGET[HOME]: define a página inicial (obrigatótio) */
		if (!array_key_exists("HOME", $this->CONFIG["TARGET"])) {
			return trigger_error("Driver[TARGET][HOME]: Information not provided.");
		}


		/* TARGET[LOGIN]: define a página de login|home (obrigatório, se exigir autenticação) */
		if ($this->CONFIG["GATEWAY"]) {
			if (!array_key_exists("LOGIN", $this->CONFIG["TARGET"])) {
				return trigger_error("Driver[TARGET][LOGIN]: Information not provided.");
			}
		} else {
			$this->CONFIG["TARGET"]["LOGIN"] = $this->CONFIG["TARGET"]["HOME"];
		}


		/* TARGET[LOGOUT]: define a página de logout|login (facultativo) */
		if (!array_key_exists("LOGOUT", $this->CONFIG["TARGET"])) {
			$this->CONFIG["TARGET"]["LOGOUT"] = $this->CONFIG["TARGET"]["LOGIN"];
		}


		/* LOG: define a lista de dados para executar a autenticação (obrigatório, se exigir autenticação) */
		if ($this->CONFIG["GATEWAY"]) {
			$check = array_key_exists("LOG", $this->CONFIG);
			$value = $check ? $this->CONFIG["LOG"] : null;
			$type  = gettype($value);
			$count = $type === "array" ? count($this->CONFIG["LOG"]) : 0;

			if ($count === 0) {
				return trigger_error("Driver[LOG]: Information not provided or inappropriate.");
			}
		} else {
			$this->CONFIG["LOG"] = null;
		}


		/* AUTH: define a função de autenticação (obrigatório, se exigir autenticação) */
		if ($this->CONFIG["GATEWAY"]) {
			$check = array_key_exists("AUTH", $this->CONFIG);
			$value = $check ? $this->CONFIG["AUTH"] : null;
			$type  = gettype($value);
			$call  = $type === "string" ? is_callable($this->CONFIG["AUTH"]) : false;

			if (!$call) {
				return trigger_error("Driver[AUTH]: Information not provided or inappropriate.");
			}
		} else {
			$this->CONFIG["AUTH"] = null;
		}


		/* CHECK: define a função de checagem de acesso a cada requisição (facultativo e só com autenticação) */
		if ($this->CONFIG["GATEWAY"]) {
			$check = array_key_exists("CHECK", $this->CONFIG);
			$value = $check ? $this->CONFIG["CHECK"] : null;
			$type  = gettype($value);
			$call  = $type === "string" ? is_callable($this->CONFIG["AUTH"]) : false;

			if ($check && !$call) {
				return trigger_error("Driver[CHECK]: Information not provided or inappropriate.");
			}
		} else {
			$this->CONFIG["CHECK"] = null;
		}


		/* TIMEOUT: define o tempo permitido entre requisições (facultativo e só com autenticação) */
		if ($this->CONFIG["GATEWAY"]) {
			$check = array_key_exists("TIMEOUT", $this->CONFIG);
			$value = $check ? $this->CONFIG["TIMEOUT"] : 0;
			$type  = gettype($value);
			$time  = $type === "integer" ? $value : intval($this->CONFIG["TIMEOUT"]);

			if ($check && $time < 1) {
				return trigger_error("Driver[TIMEOUT]: Information not provided or inappropriate.");
			}
		} else {
			$this->CONFIG["TIMEOUT"] = null;
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
	private function hash() {
		/* define o identificador da sessão a partir dos dados do usuário */

		$hash = array();

		/* checando valores que construirão o hash */
		foreach ($this->SESSION as $key => $value) {

			/* verificando se o valor participa do HASH ou não */
			if (!$value["HASH"]) {continue;}

			/* verificando se o valor tem o tipo adequado */
			if (gettype($_SESSION[$key]) !== $value["TYPE"]) {return null;}

			/* adicionando valores para compor o hash */
			if ($value["TYPE"] === "array") {
				$json   = json_encode($_SESSION[$key]);
				$hash[] = "{$key}: {$json}";
			} else {
				$hash[] = "{$key}: {$_SESSION[$key]}";
			}
		}

		/* retornando o hash */
		return md5(join(",", $hash));
	}

/*----------------------------------------------------------------------------*/
	private function log() {
		/* informa se o usuário está autenticado (boolean) */

		/* se não exigir autenticação, retorna verdadeiro */
		if (!$this->CONFIG["GATEWAY"]) {return true;}

		/* checando se os dados de sessão estão adequados */
		if (!$this->session()) {return false;}

		/* conferir os dados do hash */
		$hash = $this->hash();

		return ($hash === null || $hash !== $_SESSION["__HASH__"]) ? false : true;
	}

/*----------------------------------------------------------------------------*/
	private function login() {
		/* informa se o usuário pediu autenticação (boolean) */

		/* se já autenticado, não há como solicitar autenticação */
		if ($this->log()) {return false;}

		/* checar se os dados do POST conferem com os da autenticação (LOG) */
		if (count($_POST) !== count($this->CONFIG["LOG"])) {return false;}
		foreach ($this->CONFIG["LOG"] as $value) {
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

		/* resetando a sessão */
		$this->logout();
		$this->session();

		/* chamando a função para checar credenciais e obter dados do usuário em caso de sucesso */
		$user = call_user_func($this->CONFIG["AUTH"], $_POST);

		/* conferindo se a autenticação falhou */
		if (gettype($user) !== "array") {return false;}

		/* em caso de sucesso, definir dados da sessão */
		$_SESSION["__USER__"] = $user;
		$_SESSION["__TIME__"] = $this->time(true);
		$_SESSION["__HASH__"] = $this->hash();
		$_SESSION["__INIT__"] = $this->time();

		return true;
	}

/*----------------------------------------------------------------------------*/
	public function path() {
		/* devolve o caminho do arquivo a ser exibida */

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
