<?php
    require_once 'utilities/connection.php';
    //require_once 'utilities/validadores.php';

    class Pedido{

        function regista($idMesa, $idTipo) {
            global $conn;
            $msg = "";
            $stmt = "";
        

            $estadoPreset = 4;
        
            $stmt = $conn->prepare("INSERT INTO pedido (idMesa, idEstado) VALUES (?, ?);");
            if ($stmt === false) {
                return "Erro ao preparar statement: " . $conn->error;
            }
            $stmt->bind_param("ii", $idMesa, $estadoPreset);
        
            if ($stmt->execute()) {
                $maxIdPedido = $conn->insert_id;
                $msg = "Registado com sucesso: 'pedido' na";
            } else {
                $msg = "Erro ao registar na tabela 'pedido': " . $stmt->error;
                $stmt->close();
                return $msg;
            }
            $stmt->close();
        




            $stmt = $conn->prepare("INSERT INTO cozinha (idPedido, idPrato) VALUES (?, ?);");
            if ($stmt === false) {
                return "Erro ao preparar statement: " . $conn->error;
            }
            $stmt->bind_param("ii", $maxIdPedido, $idTipo);
        
            if ($stmt->execute()) {
                $msg .= "'cozinha'!";
            } else {
                $msg .= " Erro ao registar na tabela 'cozinha': " . $stmt->error;
            }
            $stmt->close();
        



            $conn->close();
        
            return $msg;
        }
        
        
        







        function lista() {
            global $conn;
            $msg = "<table class='table' id='tablePedidosTable'>";
            $msg .= "<thead>";
            $msg .= "<tr>";
        
            $msg .= "<th>Pedido ID</th>";
            $msg .= "<th>Cozinha ID</th>";
            $msg .= "<th>Nome da Mesa</th>";
            $msg .= "<th>Estado</th>";
            $msg .= "<th>Remover</th>";
            $msg .= "<th>Editar</th>";
        
            $msg .= "</tr>";
            $msg .= "</thead>";
            $msg .= "<tbody>";
        
            $stmt = $conn->prepare("
                SELECT 
                    pedido.*, 
                    pedido.id as pedidoID, 
                    cozinha.id as cozinhaID, 
                    mesas.nome as nomeMesa, 
                    estadopedido.descricao as descEstado
                FROM 
                    pedido, 
                    cozinha, 
                    mesas, 
                    estadopedido
                WHERE 
                    pedido.idMesa = mesas.id 
                    AND pedido.idEstado = estadopedido.id
                    AND cozinha.idPedido = pedido.id
            ");
        
            if ($stmt) {
                if ($stmt->execute()) {
                    $result = $stmt->get_result(); 
                    while ($row = $result->fetch_assoc()) {
                        $msg .= "<tr>";
                        $msg .= "<td>" . $row['pedidoID'] . "</td>";
                        $msg .= "<td>" . $row['cozinhaID'] . "</td>";
                        $msg .= "<td>" . $row['nomeMesa'] . "</td>";
                        $msg .= "<td>" . $row['descEstado'] . "</td>";
                        $msg .= "<td><button type='button' class='btn btn-danger' onclick='remover(" . $row['pedidoID'] . ", " . $row['cozinhaID'] . ")'>Remover</button></td>";
                        $msg .= "<td><button type='button' class='btn btn-primary' onclick='edita(" . $row['pedidoID'] . ", " . $row['cozinhaID'] . ")'>Editar</button></td>";
                        $msg .= "</tr>";
                    }
                } else {
                    $msg .= "<tr><td colspan='6'>Error executing query: " . $stmt->error . "</td></tr>";
                }
            } else {
                $msg .= "<tr><td colspan='6'>Error preparing query: " . $conn->error . "</td></tr>";
            }
        
            $msg .= "</tbody>";
            $msg .= "</table>";
        
            $stmt->close();
            $conn->close();
        
            return $msg;
        }
        
        
        














        function remove($pedidoID, $cozinhaID) {
            global $conn;
            $msg = "";
            $stmt1 = "";
            $stmt2 = "";
        
            $conn->begin_transaction();
        
            try {
                $stmt1 = $conn->prepare("DELETE FROM pedido WHERE id = ?");
                $stmt1->bind_param("i", $pedidoID);
                $stmt1->execute();
        
                $stmt2 = $conn->prepare("DELETE FROM cozinha WHERE idPedido = ?");
                $stmt2->bind_param("i", $pedidoID);
                $stmt2->execute();

                $conn->commit();
        
                $msg = "Registros removidos com sucesso!";
            } catch (Exception $e) {
                // ROLLBACK ";)" transaction  ";)" if any query fails
                $conn->rollback();
                // ROLLBACK ";)" transaction  ";)" if any query fails
                $msg = "Erro ao remover registros: " . $e->getMessage();
            }

            if ($stmt1) $stmt1->close();
            if ($stmt2) $stmt2->close();
            $conn->close();
        
            return $msg;
        }
        
        
        












        function getDados($pedidoID,$cozinhaID) {
            global $conn;

            $stmt1 = "";
            $stmt2 = "";
        

            $stmt1 = $conn->prepare("SELECT * FROM pedido WHERE id = ?");
            $stmt1->bind_param("i", $pedidoID);
            $stmt1->execute();
            $result = $stmt1->get_result();
            $row1 = $result->fetch_assoc();
            $stmt1->close();

    
            $stmt2 = $conn->prepare("SELECT * FROM cozinha WHERE id = ?");
            $stmt2->bind_param("i", $cozinhaID);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $row2 = $result->fetch_assoc();
            $stmt2->close();


            $row =array($row1, $row2);

            

            
            return json_encode($row);  
        }
        
        










                function edita(
        $idCliente,
        $idMesa,
        $data,
        $hora,
        $estado,
        $oldKEY) {
            global $conn;

            $msg = "";
            $stmt = "";

            $stmt = $conn->prepare("UPDATE reserva SET 
                                    idCliente = ?,
                                    idMesa = ?,
                                    data = ?,
                                    hora = ?,
                                    estado = ?
                                    WHERE id = ? ;");

            if ($stmt) { 
                $stmt->bind_param("iissii",
                $idCliente,
                $idMesa,
                $data,
                $hora,
                $estado,
                $oldKEY);

                if ($stmt->execute()) {
                    $msg = "Edição efetuada";
                } else {
                    $msg = "Erro ao editar: " . $stmt->error; 
                }
                $stmt->close(); 
            } else {
                $msg = "Erro ao preparar a declaração: " . $conn->error;  
            }

            $conn->close();

            return $msg;

        }
        
        

        



















        function uploads($img, $html_soul, $presetName, $pasta){

            $dir = "../imagens/".$pasta."/";
            $dir1 = "src/imagens/".$pasta."/";
            $flag = false;
            $targetBD = "";
        
            if(!is_dir($dir)){
                if(!mkdir($dir, 0777, TRUE)){
                    die ("Erro não é possivel criar o diretório");
                }
            }
        
            if(array_key_exists($html_soul, $img)){
                if(is_array($img)){
                    if(is_uploaded_file($img[$html_soul]['tmp_name'])){
                        $fonte = $img[$html_soul]['tmp_name'];
                        $ficheiro = $img[$html_soul]['name'];
                        $end = explode(".",$ficheiro);
                        $extensao = end($end);
                
                        $newName =$presetName.date("YmdHis").".".$extensao;
                
                        $target = $dir.$newName;
                        $targetBD = $dir1.$newName;
        
                        $this -> wFicheiro($target);
                
                        $flag = move_uploaded_file($fonte, $target);
                        
                    } 
                }
            }
            return (json_encode(array(
                "flag" => $flag,
                "target" => $targetBD
            )));
        }
        
        function wFicheiro($texto){
            $file = '../prato_Upload_logs.txt';
            if (file_exists($file)) {
                $current = file_get_contents($file);
            } else {
                $current = '';
            }
            $current .= $texto."\n";
            file_put_contents($file, $current);
        }
        


        function getSelect_mesa(){
            global $conn;
            $msg = "<option value = '-1'>Escolha uma opção</option>";
            $stmt = "";

            $stmt = $conn->prepare("SELECT * FROM mesas;");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $msg .= "<option value = '".$row['id']."'>".$row['nome']."</option>";
                }
            } else {
                $msg .= "<option value = '-1'>Sem Mesas</option>";
            }
            $stmt->close(); 
            $conn->close();
            
            return $msg;
        }
        function getSelect_pratos(){
            global $conn;
            $msg = "<option value = '-1'>Escolha uma opção</option>";
            $stmt = "";

            $stmt = $conn->prepare("SELECT * FROM pratos;");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $msg .= "<option value = '".$row['id']."'>".$row['nome']."</option>";
                }
            } else {
                $msg .= "<option value = '-1'>Sem Pratos</option>";
            }
            $stmt->close(); 
            $conn->close();
            
            return $msg;
        }
        function getSelect_estado(){
            global $conn;
            $msg = "<option value = '-1'>Escolha uma opção</option>";
            $stmt = "";

            $stmt = $conn->prepare("SELECT * FROM estadopedido;");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $msg .= "<option value = '".$row['id']."'>".$row['descricao']."</option>";
                }
            } else {
                $msg .= "<option value = '-1'>Sem Estados</option>";
            }
            $stmt->close(); 
            $conn->close();
            
            return $msg;
        }










    }

    
?>