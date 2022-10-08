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

/* para efetuar testes na biblioteca (desligar na produção) */
if (false) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

/*----------------------------------------------------------------------------*/

class Driver {

/*----------------------------------------------------------------------------*/

	/* -- DADOS -- */
	private $VERSION = "v1.0.0"; /* registra versão da biblioteca */
	private $CONFIG;             /* registra os dados de configuração */
	private $STATUS;             /* regista o identificador do progresso */
	private $PATH = false;       /* regista se método path já foi executado (só pode uma vez) */
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
	private $STRUCT = array(     /* registra restrições de CONFIG */
		"CHECK" => array( /* informa se deseja checar os dados de configuração */
			"REQUIRED" => false,
			"TYPE"     => "boolean"
		),
		"HOME" => array( /* informa a página principal */
			"REQUIRED" => true,
			"TYPE"     => "file"
		),
		"ID" => array( /* informa os identificadores e suas páginas secundárias */
			"REQUIRED" => true,
			"TYPE"     => "array",
			"BADKEYS"  => array("HOME", "EXIT"),
			"TYPELIST" => "file"
		),
		"LOG" => array( /* informações sobre a autenticação, se for o caso */
			"REQUIRED" => false,
			"TYPE"     => "array",
			"KEYS"     => array(
				"GATEWAY" => array( /* página de autenticação e de checagem */
					"REQUIRED" => true,
					"TYPE"     => "file"
				),
				"DATA" => array( /* nome dos formulários que receberão os dados de autenticação */
					"REQUIRED" => true,
					"TYPE"     => "array",
					"SIZE"     => 1,
					"TYPELIST" => "string"
				),
				"LOGIN" => array( /* função que fará a autenticação */
					"REQUIRED" => true,
					"TYPE"     => "function"
				),
				"ALLOW" => array( /* função que fará a checagem a cada acesso */
					"REQUIRED" => false,
					"TYPE"     => "function"
				),
				"LOAD" => array( /* função a ser chamada após definição da requisição  */
					"REQUIRED" => false,
					"TYPE"     => "function"
				),
				"TIME" => array( /* intervalos de tempo, em segundos, permitido entre requisições */
					"REQUIRED" => false,
					"TYPE"     => "integer",
					"SIZE"     => 1
				)
			)
		)
	);
	private $SESSION = array( /* Dados de sessão SESSION[__DRIVER__] */
		"USER" => null,   /* dados do usuário (fixo) [mixed] */
		"TIME" => null,   /* momento do login (fixo) [integer] */
		"DATE" => null,   /* momento do login (fixo) [string] */
		"HASH" => null,   /* identificador do login (fixo) [string] */
		"LOG"  => array() /* histórico de ações (variável) [array] */
	);
/*----------------------------------------------------------------------------*/
	public function __construct($config) {

		/* obter, definir e checar dados de configuração */
		$this->config($config);

		/* iniciar sessão */
		$this->start();

		/* definir status */
		$this->STATUS = 0;
		return;
	}

/*----------------------------------------------------------------------------*/
	private function start() {
		/* inicia dados da sessão */

		/* iniciar se não iniciada */
		if (!isset($_SESSION)) {
			session_start();
		}

		/* definir chave principal de sessão */
		if (!array_key_exists("__DRIVER__", $_SESSION)) {
			$_SESSION["__DRIVER__"] = array();
		}
		/* definir chaves secundárias de sessão */
		foreach ($this->SESSION as $key => $value) {
			if (!array_key_exists($key, $_SESSION["__DRIVER__"])) {
				$_SESSION["__DRIVER__"][$key] = $value;
			}
		}
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
				$this->error("CONFIG", "Error reading configuration file");
			}

			/* 2) tentar decodifica o JSON para array */
			$array = json_decode($content, true);
			if ($array === null) {
				$this->error("CONFIG", "Error in JSON file structure");
			}

			/* 3) checar o dado primário do JSON é um array */
			if (gettype($array) !== "array") {
				$this->error("CONFIG", "Inadequate configuration data");
			}

			/* 4) definir CONFIG */
			$this->CONFIG = $array;

		} elseif (gettype($config) === "array") {
			/* definir direto se for um array */
			$this->CONFIG = $config;

		} else {
			/* no caso de falhar com o arquivo ou array */
			$this->error("CONFIG", "Inappropriate argument");
		}

		/* checar dados de CONFIG, se assim estiver definido */
		if (!array_key_exists("CHECK", $this->CONFIG) || $this->CONFIG["CHECK"] !== false) {
			$this->check();
		}

		return;
	}

/*----------------------------------------------------------------------------*/
	private function check($ref = null) {

		/* obter dados */
		$data   = $ref === null ? $this->CONFIG : $this->CONFIG[$ref];
		$struct = $ref === null ? $this->STRUCT : $this->STRUCT[$ref]["KEYS"];

		/* looping pelas restrições */
		foreach ($struct as $id => $check) {

			$info   = $ref === null ? "CONFIG.{$id}" : "CONFIG.{$ref}.{$id}";

			/* se chave não obrigatória e não foi definida: null */
			if ($check["REQUIRED"] === false && !array_key_exists($id, $data)) {
				if ($ref === null) {$this->CONFIG[$id]       = null;}
				else               {$this->CONFIG[$ref][$id] = null;}

			} else {
				/* se chave é obrigatória: checar */
				if (!array_key_exists($id, $data)) {
					$this->error($info, "Information not provided");
				}

				/* checar o tipo */
				if (!$this->matchType($check["TYPE"], $data[$id])) {
					$this->error($info, "Inappropriate information");
				}

				/* checar valor mínimo */
				if (array_key_exists("SIZE", $check)) {
					$size = $check["TYPE"] === "array" ? count($data[$id]) : $data[$id];
					if ($size < $check["SIZE"]) {
						$this->error($info, "Insufficient data");
					}
				}

				/* checar identificadores proibidos */
				if (array_key_exists("BADKEYS", $check)) {
					foreach($data[$id] as $key => $item) {
						if (in_array($key, $check["BADKEYS"])) {
							$this->error($info, "Inappropriate identifier ($key)");
						}
					}
				}

				/* checar tipos da lista */
				if (array_key_exists("TYPELIST", $check)) {
					foreach($data[$id] as $key => $item) {
						if (!$this->matchType($check["TYPELIST"], $item)) {
							$this->error($info, "Inappropriate information ({$key})");
						}
					}
				}

				/* checar subitens */
				if (array_key_exists("KEYS", $check)) {
					$this->check($id);
				}
			}

		}

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
		return is_file($path) ? true : false;
	}

/*----------------------------------------------------------------------------*/
	private function error($root, $msg) {
		/* exibe uma mensagem de erro e para a interpretação */
		trigger_error("Driver (<code>{$root}</code>): {$msg} ");
		exit;
	}

/*----------------------------------------------------------------------------*/
	private function isFunction($name) {
		/* verifica se a informação é uma função possível de chamar (boolean) */
		if (gettype($name) !== "string") {return false;}
		return is_callable($name) ? true : false;
	}

/*----------------------------------------------------------------------------*/
	private function matchType($type, $value) {
		/* verifica se o valor confere com o tipo informado (boolean) */
		switch($type) {
			case "file":
				if ($this->isFile($value))     {return true;}
			case "function":
				if($this->isFunction($value))  {return true;}
			default:
				if ($type === gettype($value)) {return true;}
		}
		return false;
	}

/*----------------------------------------------------------------------------*/
	private function hash() {
		/* define o identificador da sessão a partir dos dados do usuário */

		/* checando se precisa de autenticação e os valores de sessão */
		if ($this->CONFIG["LOG"] === null)            {return null;}
		if ($_SESSION["__DRIVER__"]["USER"] === null) {return null;}
		if ($_SESSION["__DRIVER__"]["TIME"] === null) {return null;}
		if ($_SESSION["__DRIVER__"]["DATE"] === null) {return null;}

		/* definindo dados do hash */
		$hash = array(
			"USER" => $_SESSION["__DRIVER__"]["USER"],
			"TIME" => $_SESSION["__DRIVER__"]["TIME"],
			"DATE" => $_SESSION["__DRIVER__"]["DATE"],
			"IP"   => $_SERVER["REMOTE_ADDR"]
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

		/* se o método não for POST, não está solicitando autenticação */
		if ($_SERVER["REQUEST_METHOD"] !== "POST") {return false;}

		/* se não exigir altenticação, não tem o que verificar */
		if ($this->CONFIG["LOG"] === null) {return false;}

		/* se já autenticado, não há como solicitar autenticação */
		if ($this->log()) {return false;}

		/* checar se a última página foi de login */
		if ($this->lastRequest("PATH") !== $this->CONFIG["LOG"]["GATEWAY"]) {return false;}

		/* checar se o arquivo executável é o mesmo */
		if ($this->lastRequest("INDEX") !== $_SERVER["SCRIPT_NAME"]) {return false;}

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
			if (array_key_exists("__DRIVER__", $_SESSION)) {
				unset($_SESSION["__DRIVER__"]);
			}
			$this->start();
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
		$_SESSION["__DRIVER__"]["TIME"] = $this->time();
		$_SESSION["__DRIVER__"]["DATE"] = $this->time(true);
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
		$allow = $this->CONFIG["LOG"]["ALLOW"];
		if ($allow !== null) {
			$user  = $_SESSION["__DRIVER__"]["USER"];
			$check = call_user_func($allow, $user, $id, $path);
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

		/* definir chamada de PATH (só poderá chamar uma vez) */
		if ($this->PATH === true) {
			$this->error("path()", "Method can only be called once.");
		} else {
			$this->PATH = true;
		}

		/* obtendo resultado da requisição */
		$log  = $this->CONFIG["LOG"];
		$path = $log === null ? $this->freeAccess() : $this->restrictedAccess();

		/* verificando se é o caso de limpar a sessão (STATUS sessão encerrada e sessão expirada) */
		if (in_array($this->STATUS, array(7, 8))) {
			$this->logout();
		}

		/* registrando histórico de navegação no log */
		$this->history($path);

		/* checar necessidade de desvio da página, exceto entradas e saídas */
		if ($this->CONFIG["LOG"] !== null && $this->CONFIG["LOG"]["LOAD"] !== null) {
			$load = call_user_func($this->CONFIG["LOG"]["LOAD"], $this->debug());

			/* se o retorno foi um arquivo válido, retorná-lo para path */
			if ($this->isFile($load)) {
				$this->STATUS = 9;
				$this->history($load);
				return $load;
			}

			/* se for HOME */
			if ($load === "HOME") {
				$this->STATUS = 9;
				$this->history($this->CONFIG["HOME"]);
				return $this->CONFIG["HOME"];
			}

			/* se for EXIT */
			if ($load === "EXIT") {
				$this->STATUS = 9;
				$this->logout();
				$this->history($this->CONFIG["LOG"]["GATEWAY"]);
				return $this->CONFIG["LOG"]["GATEWAY"];
			}

			/* se for um identificador, retornar o arquivo correspondete como path */
			if (gettype($load) === "string" && array_key_exists($load, $this->CONFIG["ID"])) {
				$this->STATUS = 9;
				$this->history($this->CONFIG["ID"][$load]);
				return $this->CONFIG["ID"][$load];
			}
		}

		/* retornar rota */
		return $path;
	}

/*----------------------------------------------------------------------------*/
	private function history($path) {
		/* registra o histórico de navegação */
		$id    = $this->id();
		$ID    = $this->CONFIG["ID"];
		$page  = ($id === null || !array_key_exists($id, $ID)) ? null : $ID[$id];
		$index = $_SERVER["SCRIPT_NAME"];
		$log   = $this->CONFIG["LOG"] === null ? false : true;

		$_SESSION["__DRIVER__"]["LOG"][] = array(
			"INFO"   => $this->status(true), /* informação do estatus */
			"ID"     => $id,                 /* identificador informado */
			"PAGE"   => $page,               /* página desejada */
			"PATH"   => $path,               /* página definida */
			"INDEX"  => $index,              /* módulo do sistema utilizado */
			"LOG"    => $log,                /* navegação exige senha */
			"LOGIN"  => $this->log(),        /* acesso com usuário logado */
			"STATUS" => $this->status(),     /* número do status */
			"TIME"   => $this->time(),       /* registro, em segundos, do momento da ação */
			"DATE"   => $this->time(true),   /* data e hora da ação em formato ISO */
		);
		return;
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
		/* retorna ou imprime os dados de sessão */

		if ($print === true) {
			echo "<hr/>\n<pre style=\"color: #FFFFFF; background-color: #000000; white-space: pre-wrap\">";
			print_r($_SESSION["__DRIVER__"]);
			echo "</pre>\n<hr/>";
		}

		return $_SESSION["__DRIVER__"];
	}

/*----------------------------------------------------------------------------*/
	public function json($print = false) {
		/* retorna ou imprime dados de configuração em formato JSON */

		/* obtendo informações de CONFIG */
		$data = array(
			"CHECK" => $this->CONFIG["CHECK"],
			"HOME"  => $this->CONFIG["HOME"],
			"ID"    => $this->CONFIG["ID"],
			"LOG"   => $this->CONFIG["LOG"] === null ? null : array(
				"GATEWAY" => $this->CONFIG["LOG"]["GATEWAY"],
				"DATA"    => $this->CONFIG["LOG"]["DATA"],
				"LOGIN"   => $this->CONFIG["LOG"]["LOGIN"],
				"ALLOW"   => $this->CONFIG["LOG"]["ALLOW"],
				"LOAD"    => $this->CONFIG["LOG"]["LOAD"],
				"TIME"    => $this->CONFIG["LOG"]["TIME"]
			)
		);

		/* decodificando para JSON */
		$json = json_encode($data);

		if ($json === false) {
			$this->error("json()", "Error encoding data to JSON");
			return null;
		}

		/* fazendo umas substituições para melhor visualização */
		$json = str_replace("\\", "", $json);
		$json = str_replace("}}", "\n\t}\n}", $json);
		$json = str_replace("\",\"", "\", \"", $json);
		$json = str_replace("\"LOG\":null}", "\"LOG\": null\n}", $json);

		$srpl = array(
			"CHECK"   => 0, "HOME"  => 0, "ID"    => 0, "LOG"  => 0, "GATEWAY" => 1,
			"DATA"    => 1, "LOGIN" => 1, "ALLOW" => 1, "LOAD" => 1, "TIME"    => 1
		);

		foreach ($srpl as $key => $type) {
			if ($type === 0) {
				$json = str_replace("\"{$key}\":", "\n\t\"{$key}\": ", $json);
			} elseif ($type === 1) {
				$json = str_replace("\"{$key}\":", "\n\t\t\"{$key}\": ", $json);
			}
		}

		/* imprimindo, se for o caso, e retornando */
		if ($print === true) {
			echo "<hr/>\n<pre style=\"color: #FFFFFF; background-color: #000000; white-space: pre-wrap\">";
			print_r($json);
			echo "</pre>\n<hr/>";
		}
		return $json;
	}

}
?>

