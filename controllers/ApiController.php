<?php
declare(strict_types=1);

use Phalcon\Security\JWT\Builder;
use Phalcon\Security\JWT\Signer\Hmac;
use Phalcon\Security\JWT\Token\Parser;
use Phalcon\Security\JWT\Validator;
use Phalcon\Http\Message\Request;
use Phalcon\Http\Message\Stream;

class ApiController extends \Phalcon\Mvc\Controller
{
/**
 * mysql procedures
*/
    public function indexAction()
    {

    }
    public function getArtigosAllAction(){

        $this->view->disable();

        $rawBody = $this->request->getJsonRawBody();

        if(!$rawBody || !isset($rawBody[0])) return "Sem idMR ou referencia!";

        $id_marca = $rawBody[0]->idMR;
        $ref = $rawBody[0]->referencia;

        $token = $this->request->getHeaders()['Authorization'];

        $valid = $this->validate($token);

        if(!$valid) die("Auth failed");

        $result = $this->getArtigosByPart($id_marca, $ref);

        return json_encode($result);
    }

    public function getEquivalenciasAllAction(){

        $this->view->disable();

        $rawBody = $this->request->getJsonRawBody();

        if(!$rawBody || !isset($rawBody[0])) return "Sem idMR ou referencia!";

        $id_marca = $rawBody[0]->idMR;
        $ref = $rawBody[0]->referencia;

        $token = $this->request->getHeaders()['Authorization'];

        $valid = $this->validate($token);

        if(!$valid) die("Auth failed");

        $result = $this->getEquivalenciasByPart($id_marca, $ref);

        return json_encode($result);
    }

    public function getStockAllAction()
    {

        $this->view->disable();

        $rawBody = $this->request->getJsonRawBody();

        if (!$rawBody || !isset($rawBody[0])) return "Sem idMR ou referencia!";

        $id_marca = $rawBody[0]->idMR;
        $ref = $rawBody[0]->referencia;

        $token = $this->request->getHeaders()['Authorization'];

        $valid = $this->validate($token);

        if (!$valid) die("Auth failed");

        $result = $this->getStockByPart($id_marca, $ref);

        return json_encode($result);
    }

    public function getAllByPartAction()
    {
        if (true === $this->request->isPost())
        {
            return "Metodo Post enviado, por favor tente Get";
        }
        
        if (true === $this->request->isGet()) {

        $this->view->disable();

        $rawBody = $this->request->getJsonRawBody();


        if (!$rawBody || !isset($rawBody->parts[0])) return "Nada encontrado!";
        
        if(!$rawBody->clientId)  return "ClienteId vazio";

        //validate basic auth
        $token = $this->request->getHeaders()['Authorization'];
        

        $valid = $this->validate($token);

        if (!$valid) die("Auth failed");

        //find one or many parts
        $json = $this->getParsedJson($rawBody);

        return json_encode($json);
        
        }
    }

    public function getParsedJson($rawBody){


        
        $this->insertIntoTemporary($rawBody->parts);
        

        $artigos = $this->getArtigosByPart($rawBody->clientId,$rawBody->codPostal);
        $counter = 0;

            if($artigos){
                foreach($artigos as $artigo){

                    $data = array();


                    $firstIndex = "artigo";
                    $secondIndex = "referenciasRelacionadas";
                    $thirdIndex = "stock";
                    
  

                    $data[$firstIndex] = array_slice($artigo,1,6);
                    

                    $equivalencias = $this->getEquivalenciasByPart($artigo['tipo_marca'],  $artigo['ref']);
                    

                    
                    if($equivalencias){
                        foreach($equivalencias as $equivalencia){
                            $data[$firstIndex][$secondIndex][] = $equivalencia;

                        }
                    }
                    

                    $stocks = $this->getStockByPart( $artigo['tipo_marca'],  $artigo['ref']);

                    if($stocks){
                        foreach($stocks as $stock){
                            $data[$firstIndex][$thirdIndex][] = $stock;
                            
                        }
                    }

                    $result['parts'][] = $data;

                    $counter++;
                }
            }


        return $result;
    }

    public function insertIntoTemporary($rawBody){



        $sql = "DROP TABLE IF EXISTS tmp_artigos; CREATE TEMPORARY TABLE tmp_artigos(
            ArtigoID varchar(20),
            TTECFabricanteID int
        ); INSERT INTO tmp_artigos(ArtigoID,TTECFabricanteID) VALUES(";
        
        foreach($rawBody as $r){

            $ref = filter_var($r->referencia, FILTER_SANITIZE_STRING);
            $id_marca = (int)$r->idMR;

            $sql .="'{$ref}','{$id_marca}'),(";

        }
        
        $sql =  substr($sql, 0, -2) . ";";

        $query = $this->db->query($sql);
    }

    public function validate($token){

        $tokenServer = $this->getTokenBasicAuth();

        return $token == $tokenServer;
    }

    public function getTokenBasicAuth(){

        $username = "api_root";
        $password = "123456sofrapa2022x";

        return "Basic ".base64_encode("$username:$password");

    }

    public function getArtigosByPart($clientId,$codPostal){
        
        $codPostal = filter_var($r->referencia, FILTER_SANITIZE_STRING);
        
        $sql = "CALL get_articles_dets('$clientId','$codPostal')";

        $query = $this->db->query($sql);
        $query->setFetchMode(\Phalcon\Db\Enum::FETCH_ASSOC);
        $result = $query->fetchAll();

        $count = $query->numRows();
        

        if($count < 1) return null;

        return $result;
    }

    public function getEquivalenciasByPart($id_marca, $ref){

        $sql = "CALL get_tara('$id_marca','$ref')";

        $query = $this->db->query($sql);
        $query->setFetchMode(\Phalcon\Db\Enum::FETCH_ASSOC);
        $result = $query->fetchAll();

        $count = $query->numRows();

        if($count < 1) return null;
        


        return $result;
    }

    public function getStockByPart($id_marca, $ref){
        
        $sql = "CALL get_stock('$ref','$id_marca')";

        $query = $this->db->query($sql);
        $query->setFetchMode(\Phalcon\Db\Enum::FETCH_ASSOC);
        $result = $query->fetchAll();

        $count = $query->numRows();
                   
        if($count < 1) return null;
        

        return $result;
    }



}

