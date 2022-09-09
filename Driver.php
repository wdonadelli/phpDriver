<?php
class Driver {

/*----------------------------------------------------------------------------*/

	/* -- CONFIG -- */
	private $GATEWAY; /* registra se haverá autenticação (boolean: false) */
	private $TARGET;  /* registra os identificadores e endereços [file] */
	private $LOG;     /* registra informações vindas do login [string] */
	private $AUTH;    /* registra o nome da função autenticadora [string] */
	private $CHECK;   /* registra o nome da função que checa cada requisição [string] */
	private $TIMEOUT; /* registra o tempo entre requisições (em segundos) [number] */

	/* -- SESSÃO -- */
	private $DATA = array( /* [tipo do campo esperado, valor inicial, se participa do hash] */
		"__USER__" => array("TYPE" => "array",   "INITIAL" => null, "HASH" => true),
		"__TIME__" => array("TYPE" => "string",  "INITIAL" => null, "HASH" => true),
		"__HASH__" => array("TYPE" => "string",  "INITIAL" => null, "HASH" => false),
		"__INIT__" => array("TYPE" => "integer", "INITIAL" => null, "HASH" => false)
	);

	/* -- SISTEMA -- */
	private $PROGRESS = array ( /* registra as fases da requisição */
		0 => "SESSION STARTED", /* antes de chamar url */
		1 => "AUTHENTICATION REQUIRED",
		2 => "AUTHENTICATION FAILED",
		3 => "SUCCESSFULLY AUTHENTICATED",
		4 => "PERMITTED ACCESS",
		5 => "ACCESS DENIED",
		6 => "PAGE NOT FOUND",
		7 => "SESSION CLOSED",
		8 => "SESSION EXPIRED"
	);
	private $STATUS;             /* regista o identificador do progresso */
	private $VERSION = "v1.0.0"; /* registra versão da biblioteca */
	private $ERROR;              /* registra as mensagens de erro no construtor */

/*----------------------------------------------------------------------------*/
	public function __construct($config, $force = false) {

		/* verificar argumento */
		$this->config($config, $force);

		/* iniciar sessão */
		session_start();

		/* verificando se há erro no config */
		if ($this->ERROR !== null) {
			$this->logout();
			trigger_error($this->ERROR);
			exit;
		}

		/* definir sessão */
		$this->session();
		$this->STATUS = 0;

		return;
	}

/*----------------------------------------------------------------------------*/
	private function session($check = false) {
		/* define ou checa dados da sessão, se nenecessário */

		/* definindo variáveis privadas da sessão */
		foreach ($this->DATA as $id => $data) {
			$exists = $this->exists($id, $_SESSION);
			$type   = $exists ? gettype($_SESSION[$id]) : null;
			$match  = $data["TYPE"] === $type ? true : false;

			/* checando se os valores estão adequados */
			if ($check === true && !$match) {
				return false;
			}
			/* definindo valores iniciais */
			if ($check !== true && (!$exists || !$match)) {
				$_SESSION[$id] = $data["INITIAL"];
			}
		}
		return true;
	}

/*----------------------------------------------------------------------------*/
	private function config($input, $force) {
		/* verifica se o config foi informado adequadamente */

		/* CONFIG ................................................................*/
		if (gettype($input) === "string") { /* verificando se trata de um arquivo JSON */
			/* checando se é um arquivo */
			if (!is_file($input)) {
				return $this->ERROR = "Driver: Configuration file not found.";
			}
			/* lendo e o conteúdo do arquivo e testando-o */
			$content = file_get_contents($input);
			if ($content === false) {
				return $this->ERROR = "Driver: Error reading configuration file.";
			}
			/* decodificando o JSON em array e testando-o */
			$array = json_decode($content, true);
			if ($array === null) {
				return $this->ERROR = "Driver: Error in JSON file structure.";
			}
			/* redefinindo variável */
			$input = $array;
		} elseif (gettype($input) !== "array") { /* checando se não é um array */
			return $this->ERROR = "Driver: Inadequate configuration data.";
		}

		/* FORCE .................................................................*/
		if ($force === true) { /* sem verificação de erro */
			$this->GATEWAY = $input["GATEWAY"];
			$this->TARGET  = $input["TARGET"];
			$this->LOG     = $input["LOG"];
			$this->AUTH    = $input["AUTH"];
			$this->CHECK   = $input["CHECK"];
			$this->TIMEOUT = $input["TIMEOUT"];
			return;
		}

		/* GATEWAY ...............................................................*/
		$this->GATEWAY = true;
		if (!$this->exists("GATEWAY", $input) || $input["GATEWAY"] !== true) {
			$this->GATEWAY = false;
		}

		/* TARGET ................................................................*/
		$this->TARGET = array();
		if (!$this->exists("TARGET", $input) || gettype($input["TARGET"]) !== "array") {
			return $this->ERROR = "Driver[TARGET]: Information not provided or inappropriate";
		}
		foreach($input["TARGET"] as $id => $path) {
			if (gettype($path) !== "string") { /* é uma string? */
				return $this->ERROR = "Driver[TARGET][{$id}]: The value must be a string.";
			}
			if (!is_file($path)) { /* é um caminho válido? */
				return $this->ERROR = "Driver[TARGET][{$id}]: No file found.";
			}
			$this->TARGET["{$id}"] = $path;
		}

		/* TARGET:HOME ...........................................................*/
		if (!$this->exists("HOME", $this->TARGET)) {
			return $this->ERROR = "Driver[TARGET][HOME]: Information not provided.";
		}
		if (!is_file($this->TARGET["HOME"])) {
			return $this->ERROR = "Driver[TARGET][HOME]: No file found.";
		}


		/* TARGET:LOGIN ..........................................................*/
		if (!$this->GATEWAY) {
			$this->TARGET["LOGIN"] = $this->TARGET["HOME"];
		} else {
			if (!$this->exists("LOGIN", $this->TARGET)) {
				return $this->ERROR = "Driver[TARGET][LOGIN]: Information not provided.";
			}
			if (!is_file($this->TARGET["LOGIN"])) {
				return $this->ERROR = "Driver[TARGET][LOGIN]: No file found.";
			}
		}

		/* TARGET:LOGOUT .........................................................*/
		if (!$this->GATEWAY) {
			$this->TARGET["LOGOUT"] = $this->TARGET["HOME"];
		} else {
			if (!$this->exists("LOGOUT", $this->TARGET)) {
				$this->TARGET["LOGOUT"] = $this->TARGET["LOGIN"];
			}
			elseif (!is_file($this->TARGET["LOGOUT"])) {
				return $this->ERROR = "Driver[TARGET][LOGOUT]: No file found.";
			}
		}

		/* LOG ...................................................................*/
		if ($this->GATEWAY) {
			if (!$this->exists("LOG", $input) || gettype($input["LOG"]) !== "array") {
				return $this->ERROR = "Driver[LOG]: Information not provided or inappropriate";
			}
			$this->LOG = $input["LOG"];
		} else {
			$this->LOG = array();
		}

		/* AUTH ..................................................................*/
		if ($this->GATEWAY) {
			if (!$this->exists("AUTH", $input) || !is_callable($input["AUTH"])) {
				return $this->ERROR = "Driver[AUTH]: Information not provided or inappropriate";
			}
			$this->AUTH = $input["AUTH"];
		} else {
			$this->AUTH = null;
		}

		/* CHECK .................................................................*/
		if ($this->GATEWAY && $this->exists("CHECK", $input)) {
			if (!is_callable($input["CHECK"])) {
				return $this->ERROR = "Driver[CHECK]: Information not provided or inappropriate";
			}
			$this->CHECK = $input["CHECK"];
		} else {
			$this->CHECK = null;
		}

		/* TIMEOUT ...............................................................*/
		if ($this->GATEWAY && $this->exists("TIMEOUT", $input)) {
			if (gettype($input["TIMEOUT"]) === "integer") {
				$this->TIMEOUT = $input["TIMEOUT"];
			} else {
				return $this->ERROR = "Driver[TIMEOUT]: Information not provided or inappropriate";
			}
		} else {
			$this->TIMEOUT = null;
		}

		/*........................................................................*/
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
	private function exists($id, $array) {
		/* informa se o identificador existe no array (boolean) */
		return array_key_exists($id, $array) ?  true : false;
	}

/*----------------------------------------------------------------------------*/
	private function hash() {
		/* define o identificador da sessão a partir dos dados do usuário */

		$hash = array();

		/* checando valores que construirão o hash */
		foreach ($this->DATA as $id => $data) {

			/* verificando se o valor participa do HASH ou não */
			if (!$data["HASH"]) {continue;}

			/* verificando se o valor tem o tipo adequado */
			if (gettype($_SESSION[$id]) !== $data["TYPE"]) {return null;}

			/* adicionando valores para compor o hash */
			if ($data["TYPE"] === "array") { /* padrão: identificador em ordem ascendente se array */
				$keys = array_keys($_SESSION[$id]);
				asort($keys);
				foreach($keys as $key) {
					$hash[] = "{$id}[{$key}]: {$_SESSION[$id][$key]}";
				}
			} else {
				$hash[] = "{$id}: {$_SESSION[$id]}";
			}
		}

		/* retornando o hash */
		return md5(join(",", $hash));
	}

/*----------------------------------------------------------------------------*/
	private function log() {
		/* informa se o usuário está autenticado (boolean) */

		/* se não exigir autenticação, retorna verdadeiro */
		if (!$this->GATEWAY) {return true;}

		/* checando dados de sessão */
		if (!$this->session(true)) {return false;}

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
		if (count($_POST) !== count($this->LOG)) {return false;}
		foreach($this->LOG as $value) {
			if (!$this->exists($value, $_POST)) {return false;}
		}

		return true;
	}

/*----------------------------------------------------------------------------*/
	private function logout() {
		/* encerra a sessão */
		session_unset();
		session_destroy();
		return;
	}

/*----------------------------------------------------------------------------*/
	private function register() {
		/* registra a autenticação e devolve o resultado (boolean) */

		/* limpando sessão */
		session_unset();
		$this->session();

		/* chamando a função para checar credenciais e obter dados do usuário em caso de sucesso */
		$user = call_user_func($this->AUTH, $_POST);

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

		$gateway = $this->GATEWAY;
		$home    = $this->TARGET["HOME"];
		$login   = $this->TARGET["LOGIN"];
		$logout  = $this->TARGET["LOGOUT"];
		$log     = $this->LOG;
		$timeout = $this->TIMEOUT;
		$id      = $this->exists("id", $_GET) ? $_GET["id"] : "HOME";

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

		/* 2) página requisitada existe */
		if ($this->exists($id, $this->TARGET)) {

			/* 2.1: verificar acesso, se for o caso */
			if ($this->CHECK !== null) {
				/* chamando função verificadora */
				$check = call_user_func($this->CHECK, $_SESSION["__USER__"], $id, $this->TARGET[$id]);

				if ($check) { /* acesso permitido: requisição */
					$this->STATUS = 4;
					return $this->TARGET[$id];
				} else { /* acesso negado: home */
					$this->STATUS = 5;
					return $this->TARGET["HOME"];
				}
			}

			/* 2.2: direcionar para a página */
			$this->STATUS = 4;
			return $this->TARGET[$id];
		}

		/* 3) página requisitada não existe: home */
		$this->STATUS = 6;
		return $this->TARGET["HOME"];
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
	public function debug($json = false) {
		/* imprime os dados de sessão e de configuração */
		$data = array();
		$data["GATEWAY"]  = $this->GATEWAY;
		$data["TARGET"]   = $this->TARGET;
		$data["LOG"]      = $this->LOG;
		$data["AUTH"]     = $this->AUTH;
		$data["CHECK"]    = $this->CHECK;
		$data["TIMEOUT"]  = $this->TIMEOUT;
		/* imprimindo */
		echo "<pre>";
		if ($json === true) {
			echo json_encode($data);
		} else {
			$data["STATUS"]   = $this->STATUS;
			$data["PROGRESS"] = $this->PROGRESS;
			$data["_SESSION"] = $_SESSION;
			print_r($data);
		}
		echo "</pre>";
		return;
	}


}

?>
