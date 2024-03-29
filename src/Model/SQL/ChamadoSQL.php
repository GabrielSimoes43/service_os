<?php

namespace Src\Model\SQL;


class ChamadoSQL
{

    public static function AbrirChamadoSQL()
    {
        $sql = 'INSERT into tb_chamado (data_abertura, descricao_problema, funcionario_id, alocar_id) VALUES (?,?,?,?)';
        return $sql;
    }

    public static function ATUALIZAR_ALOCAMENTO()
    {
        $sql = 'UPDATE tb_alocar set situacao = ?
                where id = ?';
        return $sql;
    }

    public static function FILTRAR_CHAMADO($tipo)
    {

        $sql = 'SELECT DATE_FORMAT(ch.data_abertura, "%d/%m/%Y às %Hh%i") as data_abertura,
                       ch.descricao_problema,
                       DATE_FORMAT(ch.data_atendimento, "%d/%m/%Y às %Hh%i") as data_atendimento,
                       DATE_FORMAT(ch.data_encerramento, "%d/%m/%Y às %Hh%i") as data_encerramento,
                       ch.laudo_tecnico,
                       us_fu.nome as nome_funcionario,
                       us_te.nome as nome_tecnico,
                       eq.identificacao,
                       mo.nome as nome_modelo,
                       ti.nome as nome_tipo,
                       (SELECT nome From tb_usuario Where id = ch.tecnico_encerramento) as tecnico_encerramento
	                    FROM
	                    tb_chamado as ch
	                    INNER JOIN tb_funcionario as fu
	                    on fu.funcionario_id = ch.funcionario_id	
	                    INNER JOIN tb_usuario as us_fu
	                    on us_fu.id = fu.funcionario_id
	                    LEFT JOIN tb_tecnico as te
	                    on te.tecnico_id = ch.tecnico_atendimento
	                    LEFT JOIN tb_usuario as us_te
	                    on us_te.id = te.tecnico_id
	                    INNER JOIN tb_alocar as al
	                    on al.id = ch.alocar_id
	                    INNER JOIN tb_equipamento as eq
	                    on eq.id = al.equipamento_id
	                    INNER JOIN tb_modeloequip as mo
	                    on mo.id = eq.modeloequip_id
	                    INNER JOIN tb_tipoequip as ti
	                    on ti.id = eq.tipoequip_id
                        ';
        switch ($tipo) {
            case '1':
                $sql .= ' WHERE data_atendimento is null';
                break;
            case '2':
                $sql .= ' WHERE ch.data_atendimento is not null AND ch.data_encerramento is null';
                break;
            case '3':
                $sql .= ' WHERE ch.data_encerramento is not null';
                break;
        }
        return $sql;
    }
    public static function FILTRAR_CHAMADO_ABERTO(){
        $sql = 'SELECT count(0) as QuantidadeChamado 
        from tb_chamado
            where data_abertura is not null and data_atendimento is null and data_encerramento is null';

        return $sql;
    }
    public static function FILTRAR_CHAMADO_GERAL($tipo, $setorID)
    {

        $sql = 'SELECT ch.id,
                       DATE_FORMAT(ch.data_abertura, "%d/%m/%Y às %Hh%i") as data_abertura,
                       ch.descricao_problema,
                       DATE_FORMAT(ch.data_atendimento, "%d/%m/%Y às %Hh%i") as data_atendimento,
                       DATE_FORMAT(ch.data_encerramento, "%d/%m/%Y às %Hh%i") as data_encerramento,
                       ch.laudo_tecnico,
                       ch.tecnico_atendimento,
                       ch.tecnico_encerramento,
                       us_fu.nome as nome_funcionario,
                       us_te.nome as nome_tecnico,
                       eq.identificacao,
                       mo.nome as nome_modelo,
                       ti.nome as nome_tipo,
                       al.id as idAlocado, 
                       (SELECT nome From tb_usuario Where id = ch.tecnico_encerramento) as tecnico_encerramento
	                    FROM
	                    tb_chamado as ch
	                    INNER JOIN tb_funcionario as fu
	                    on fu.funcionario_id = ch.funcionario_id	
	                    INNER JOIN tb_usuario as us_fu
	                    on us_fu.id = fu.funcionario_id
	                    LEFT JOIN tb_tecnico as te
	                    on te.tecnico_id = ch.tecnico_atendimento
	                    LEFT JOIN tb_usuario as us_te
	                    on us_te.id = te.tecnico_id
	                    INNER JOIN tb_alocar as al
	                    on al.id = ch.alocar_id
	                    INNER JOIN tb_equipamento as eq
	                    on eq.id = al.equipamento_id
	                    INNER JOIN tb_modeloequip as mo
	                    on mo.id = eq.modeloequip_id
	                    INNER JOIN tb_tipoequip as ti
	                    on ti.id = eq.tipoequip_id';

        switch ($tipo) {
            case '1':
                $sql .= ' WHERE data_atendimento is null';
                break;
            case '2':
                $sql .= ' WHERE data_atendimento is not null AND data_encerramento is null';
                break;
            case '3':
                $sql .= ' WHERE data_encerramento is not null';
                break;
        }
        if (!empty($setorID)) {
            $sql .= ' AND al.setor_id = ?';
        }
        $sql .= ' Order by data_encerramento desc, data_abertura desc';
        return $sql;
    }

    public static function ATENDER_CHAMADO()
    {
        $sql = ' UPDATE tb_chamado set data_atendimento = ?, tecnico_atendimento = ? WHERE id = ?';
        return $sql;
    }
    public static function FINALIZAR_CHAMADO()
    {
        $sql = ' UPDATE tb_chamado set data_encerramento = ?, tecnico_encerramento = ?, laudo_tecnico = ? WHERE id = ?';
        return $sql;
    }

    public static function CarregarDadosChamadoSQL()
    {
        $sql = 'SELECT 
                    COUNT(c.id) AS Total, 
                    SUM(CASE WHEN c.data_atendimento IS NULL AND c.data_encerramento IS NULL THEN 1 ELSE 0 END) AS Aguardando,
                    SUM(CASE WHEN c.data_atendimento IS NOT NULL AND c.data_encerramento IS NULL THEN 1 ELSE 0 END) AS Em_atendimento,
                    SUM(CASE WHEN c.data_encerramento IS NOT NULL THEN 1 ELSE 0 END) AS Encerrando
                FROM tb_chamado c';

        return $sql;
    }

    public static function ChamadosPorFuncionarioSQL(){
        $sql = 'SELECT us.nome, COUNT(*) as total_chamados, (SELECT SUM(total_chamados) FROM (SELECT COUNT(*) as total_chamados FROM tb_usuario us2 JOIN tb_chamado ch2 ON us2.id = ch2.funcionario_id GROUP BY us2.nome) as subquery) as total_geral
                FROM tb_usuario us
                     JOIN tb_chamado ch ON us.id = ch.funcionario_id
                            GROUP BY us.nome';
        return $sql;
    }


    public static function ChamadosPorPeriodoSQL(){
        $sql = 'SELECT YEAR(data_abertura) AS ano, MONTH(data_abertura) AS mes, COUNT(*) AS total_chamados
                FROM tb_chamado
                    GROUP BY ano, mes
                    ORDER BY ano, mes;';
        return $sql;
    }

    public static function ChamadosPorSetorSQL(){

        $sql = 'SELECT tb_setor.nome_setor, COUNT(*) as quantidade_por_setor
                FROM tb_chamado
                    INNER JOIN tb_alocar ON tb_chamado.alocar_id = tb_alocar.id
                    INNER JOIN tb_setor ON tb_alocar.setor_id = tb_setor.id
                    GROUP BY tb_setor.nome_setor;';
        return $sql;
    }



}
