<?php
// don't load directly
if ((stristr( $_SERVER['REQUEST_URI'], "session.php") ) || ( !defined('T3_ABSPATH') )) die("no access");
#   TemaTres : aplicación para la gestión de lenguajes documentales #       #
#                                                                        #
#   Copyright (C) 2004-2017 Diego Ferreyra tematres@r020.com.ar
#   Distribuido bajo Licencia GNU Public License, versión 2 (de junio de 1.991) Free Software Foundation
#
###############################################################################################################
# Funciones HTML. #

# Armado de resultados de búsqueda
#
function resultaBusca($texto,$tipo=""){

	GLOBAL $CFG;

	$texto=XSSprevent(html2txt($texto));


	//Anulación de sugerencia de términos
	$sgs=$_GET["sgs"];

	//Ctrol lenght string
	if((strlen($texto)>=CFG_MIN_SEARCH_SIZE) || ($tipo=='E'))	{
		$sql= ($tipo=='E') ? SQLbuscaExacta("$texto") : SQLbuscaSimple("$texto");
		$sql_cant=SQLcount($sql);
		$classMensaje= ($sql_cant>0) ? 'information' : 'warning';
	}	else	{
		$sql_cant='0';
		$resumeResult = '<p class="alert alert-danger" role="alert">'.sprintf(MSG_minCharSerarch,stripslashes($texto),strlen($texto),CFG_MIN_SEARCH_SIZE-1).'</p>';
	}

	$body.='<div class="container" id="bodyText"><h2>'.LABEL_busqueda.'</h2>';

	$body.=$resumeResult;

	if($sql_cant==0) $body.= '<p class="alert alert-danger" role="alert"><strong>0</strong> '.MSG_ResultBusca.' <strong> "<em>'.stripslashes($texto).'</em>"</strong></p>';

	if($sql_cant>0){
		$row_result.='<div class="tab-pane fade in active" id="results">';

		$row_result.= '<p class="alert alert-info" role="alert"><strong>'.$sql_cant.'</strong> '.MSG_ResultBusca.' <strong> "<em>'.stripslashes($texto).'</em>"</strong></p>';
		$row_result.='<ul>';

		while($resulta_busca=$sql->FetchRow()){

			$ibusca=++$ibusca;
			$acumula_indice.=$resulta_busca["indice"];

			$acumula_temas.=$resulta_busca["id_definitivo"].'|';

			if($ibusca=='1')
			{
				//Guardar el primer término para ver si hay coincidencia exacta
				$primerTermino=$resulta_busca["tema"];
				$primerTermino_id=($resulta_busca["id_definitivo"]) ? $resulta_busca["id_definitivo"] : $resulta_busca["tema_id"];
			}

			//si hubo coicidencia exacta y están apagadas las sugerencias
			if((strtoupper($primerTermino)==trim(strtoupper($texto))) && (($_GET["sgs"]=='off') || ($sql_cant==1)))
			{
				return HTMLbodyTermino(ARRAYverDatosTermino($primerTermino_id));
			}

			$leyendaTerminoLibre=($resulta_busca[esTerminoLibre]=='SI') ? ' ('.LABEL_terminoLibre.')' : '';

			$styleClassLink= ($resulta_busca["estado_id"]!=='13') ? 'estado_termino'.$resulta_busca[estado_id] : '';
			$styleClassLinkMetaTerm= ($resulta_busca["isMetaTerm"]=='1') ? 'metaTerm' : '';

			//Si no es un término preferido
			if($resulta_busca["termino_preferido"])
			{
				switch($resulta_busca["t_relacion"])
				{
					case '4'://UF
					$leyendaConector=USE_termino;
					break;

					case '5'://Tipo relacion término equivalente parcialmente
					$leyendaConector='<abbr title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr>';
					break;

					case '6'://Tipo relacion término equivalente
					$leyendaConector='<abbr title="'.LABEL_termino_equivalente.'" lang="'.LANG.'">'.EQ_acronimo.'</abbr>';
					break;

					case '7'://Tipo relacion término no equivalente
					$leyendaConector='<abbr title="'.LABEL_termino_no_equivalente.'" lang="'.LANG.'">'.NEQ_acronimo.'</abbr>';
					break;

					case '8'://Tipo relacion término equivalente inexacta
					$leyendaConector='<abbr title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr>';
					break;
				}


				if ((in_array($resulta_busca["rr_code"],$CFG["HIDDEN_EQ"])) && (!$_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]))
				{
					$row_result.= '<li><a class="'.$styleClassLink.' '.$styleClassLinkMetaTerm.'" title="'.LABEL_verDetalle.$resulta_busca["tema"].'" href="'.URL_BASE.'index.php?tema='.$resulta_busca["id_definitivo"].'">'.$resulta_busca["termino_preferido"].'</a></li>'."\r\n" ;
				}else{
					$row_result.= '<li><em><a class="'.$styleClassLink.' '.$styleClassLinkMetaTerm.'" title="'.LABEL_verDetalle.$resulta_busca["tema"].'" href="'.URL_BASE.'index.php?tema='.$resulta_busca["tema_id"].'&amp;/'.string2url($resulta_busca["tema"]).'">'.$resulta_busca["tema"].'</a></em> '.$leyendaConector.' <a title="'.LABEL_verDetalle.$resulta_busca["tema"].'" href="'.URL_BASE.'index.php?tema='.$resulta_busca["id_definitivo"].'">'.$resulta_busca["termino_preferido"].'</a> </li>'."\r\n" ;
				}

			}
			else // es un término preferido
			{
				$row_result.='<li><a class="'.$styleClassLink.' '.$styleClassLinkMetaTerm.'"  title="'.LABEL_verDetalle.$resulta_busca["tema"].'" href="'.URL_BASE.'index.php?tema='.$resulta_busca["id_definitivo"].'&amp;/'.string2url($resulta_busca["tema"]).'">'.$resulta_busca["tema"].'</a> '.$leyendaTerminoLibre.'</li>'."\r\n" ;
			}

		};//fin del while
		$row_result.='</ul>';
		$row_result.='</div>'; //fin de div de búsqueda

		//Si no hubo coincidencia exacta
		if((strtoupper($primerTermino)!==trim(strtoupper($texto))) && ($_GET["sgs"]!='off'))
		{
			$body.=HTMLsugerirTermino($texto,$acumula_temas);
		}

		$result_suplementaTG=HTMLbusquedaExpandidaTG($acumula_indice,$acumula_temas,$texto);
		$result_suplementaTR=HTMLbusquedaExpandidaTR($acumula_temas,$texto);



		if($result_suplementaTG["count"]>0)
		{
			$row_resultTGmenu='<li><a href="#suplementaTG" data-toggle="tab">'.ucfirst(LABEL_resultados_suplementarios).' ('.$result_suplementaTG["count"].')</a></li>';
			$row_resultTG='<div class="tab-pane fade" id="suplementaTG">'.$result_suplementaTG["html"].'</div>';
		}
		if($result_suplementaTR["count"]>0)
		{
			$row_resultTRmenu='<li><a href="#suplementaTR" data-toggle="tab">'.ucfirst(LABEL_resultados_relacionados).' ('.$result_suplementaTR["count"].')</a></li>';
			$row_resultTR='<div class="tab-pane fade" id="suplementaTR">'.$result_suplementaTR["html"].'</div>';
		}


		$body.='<div id="listaBusca">';

		$body.='<ul id="myTermTab" class="nav nav-tabs" style="margin-bottom: 15px;">
		<li><a class="active" href="#results" data-toggle="tab">'.ucfirst(LABEL_results).' ('.$sql_cant.') </a></li>';
		$body.=$row_resultTGmenu.$row_resultTRmenu;
		$body.='</ul>';

		#Tabs content
		$body.='<div id="tabContent" class="tab-content">';

		$body.=$row_result.$row_resultTG.$row_resultTR;


		$body.='</div>';//fin de fiv de tabs
	}
	elseif(strlen($texto)>=CFG_MIN_SEARCH_SIZE)// Si no hay resultados y la expresión es mayor al mínimo
	{
		//sugerir o mostrar que no hay resultados
		$body.=HTMLsugerirTermino($texto);
	};// fin de if result

	$body.='</div>'; //container;
	return $body;
};


#######################################################################

#
#  ARMADOR DE HTML CON DATOS DEL TERMINO
#
function doContextoTermino($idTema,$i_profundidad){

	GLOBAL $CFG;

	//recibe de HTMLbodyTermino
	//$idTema = id del término
	//$i_profundidad= contador de profundidad

	//Terminos específicos
	$sqlNT=SQLverTerminosE($idTema);

	//Se devolverá el tema_id para utilizar en fuentes XML y como tema_id canónico
	$tema_id=$idTema;

	while ($datosNT=$sqlNT->FetchRow()){
		$int=++$int;

		if($datosNT["id_te"]){
			$link_next=' <a href="javascript:expand(\''.$datosNT["id_tema"].'\')" title="'.LABEL_verDetalle.' '.$datosNT[tema].'"><span id="expandTE'.$datosNT["id_tema"].'">&#x25ba;</span><span id="contraeTE'.$datosNT["id_tema"].'" style="display: none">&#x25bc;</span></a>';
			$link_next.=HTMLverTE($datosNT["id_tema"],$i_profundidad);
		}else{
			$link_next='';
		};

		//editor de relaciones
		if($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]){
			$td_delete='<a type="button" class="btn btn-danger btn-xs" id="elimina_'.$datosNT["id_tema"].'" title="'.LABEL_borraRelacion.'"  class="eliminar" href="'.URL_BASE.'index.php?ridelete='.$datosNT["id_relacion"].'&amp;tema='.$idTema.'" onclick="return askData();"><span class="glyphicon  icon-remove"></span></a> ';
			$row_NT.=' <li  id="t'.$datosNT[id_tema].'">'.$td_delete.'<abbr class="thesacronym" title="'.TE_termino.' '.$datosNT["rr_value"].'" lang="'.LANG.'" id="r'.$datosNT["rel_id"].'"><span class="editable_selectTE" id="edit_rel_id'.$datosNT["rel_id"].'" style="display: inline">'.TE_acronimo.$datosNT["rr_code"].'</span>'.$i_profundidad.'</abbr> ';

			//Editor de código
			$row_NT.=($CFG["_USE_CODE"]=='1') ? '<div title="term code, click to edit" class="editable_textarea" id="code_tema'.$datosNT["id_tema"].'">'.$datosNT["code"].'</div>' : '';

		}
		else
		{
			$row_NT.=' <li id="t'.$datosNT["id_tema"].'"><abbr class="thesacronym" id="r'.$datosNT["rel_id"].'" title="'.TE_termino.' '.$datosNT["rr_value"].'" lang="'.LANG.'">'.TE_acronimo.$datosNT["rr_code"].$i_profundidad.'</abbr> ';
			//ver  código
			$row_NT.=($CFG["_SHOW_CODE"]=='1') ? ' '.$datosNT["code"].' ' : '';
		}

		$css_class_MT=($datosNT["isMetaTerm"]==1) ? ' class="metaTerm" ' : '';
		$label_MT=($datosNT["isMetaTerm"]==1) ? NOTE_isMetaTerm : '';

		$row_NT.='<a '.$css_class_MT.' title="'.LABEL_verDetalle.' '.$datosNT["tema"].' ('.TE_termino.') '.$label_MT.'"  href="'.URL_BASE.'index.php?tema='.$datosNT["id_tema"].'&amp;/'.string2url($datosNT["tema"]).'">'.$datosNT["tema"].'</a>'.$link_next.'</li>';
	};

	// Terminos TG, UF y TR
	$sqlTotalRelacionados=SQLverTerminoRelaciones($tema_id);

	while($datosTotalRelacionados= $sqlTotalRelacionados->FetchRow()){

		if($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]){
			$td_delete='<a type="button" class="btn btn-danger btn-xs" title="'.LABEL_borraRelacion.'" href="'.URL_BASE.'index.php?ridelete='.$datosTotalRelacionados["id_relacion"].'&amp;tema='.$idTema.'" onclick="return askData();"><span class="glyphicon  icon-remove"></span></a> ';
			$classAcrnoyn='editable_select'.$datosTotalRelacionados["t_relacion"];
		}else{
			$td_delete='';
			$classAcrnoyn='thesacronym';
		};

		#Change to metaTerm attributes
		if(($datosTotalRelacionados["BT_isMetaTerm"]==1))
		{
			$css_class_MT= ' class="metaTerm" ';
			$label_MT=NOTE_isMetaTerm;
		}
		else
		{
			$css_class_MT= '';
			$label_MT='';
		}


		switch($datosTotalRelacionados["t_relacion"]){
			case '3':// TG
			$itg=++$itg;
			$row_TG.='          <li>'.$td_delete.'<abbr class="'.$classAcrnoyn.'" id="edit_rel_id'.$datosTotalRelacionados[rel_id].'" style="display: inline" title="'.TG_termino.' '.$datosTotalRelacionados[rr_value].'" lang="'.LANG.'">'.TG_acronimo.$datosTotalRelacionados["rr_code"].'</abbr>';
			$row_TG.='          <a '.$css_class_MT.' title="'.LABEL_verDetalle.' '.$datosTotalRelacionados["tema"].' ('.TG_termino.') '.$label_MT.'"  href="'.URL_BASE.'index.php?tema='.$datosTotalRelacionados["tema_id"].'&amp;/'.string2url($datosTotalRelacionados["tema"]).'">'.$datosTotalRelacionados["tema"].'</a></li>';
			break;

			case '4':// UF
			//hide hidden equivalent terms
			if ((!in_array($datosTotalRelacionados["rr_code"],$CFG["HIDDEN_EQ"])) || ($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]))
			{
				$iuf=++$iuf;
				$row_UP.='          <li>'.$td_delete.'<abbr class="'.$classAcrnoyn.'" id="edit_rel_id'.$datosTotalRelacionados[rel_id].'" style="display: inline" title="'.UP_termino.' '.$datosTotalRelacionados["rr_value"].'" lang="'.LANG.'">'.UP_acronimo.$datosTotalRelacionados["rr_code"].'</abbr>';
				$row_UP.='          <a class="NoTerm" title="'.LABEL_verDetalle.' '.$datosTotalRelacionados["tema"].' ('.UP_termino.')"  href="'.URL_BASE.'index.php?tema='.$datosTotalRelacionados[tema_id].'&amp;/'.string2url($datosTotalRelacionados["tema"]).'">'.$datosTotalRelacionados["tema"].'</a></li>';
			}
			break;

			case '2':// TR
			$irt=++$irt;
			$row_TR.='          <li>'.$td_delete.'<abbr class="'.$classAcrnoyn.'" id="edit_rel_id'.$datosTotalRelacionados[rel_id].'" style="display: inline" title="'.TR_termino.' '.$datosTotalRelacionados["rr_value"].'" lang="'.LANG.'">'.TR_acronimo.$datosTotalRelacionados["rr_code"].'</abbr>';
			$row_TR.='          <a '.$css_class_MT.' title="'.LABEL_verDetalle.' '.$datosTotalRelacionados["tema"].' ('.TR_termino.') '.$label_MT.'"  href="'.URL_BASE.'index.php?tema='.$datosTotalRelacionados["tema_id"].'&amp;/'.string2url($datosTotalRelacionados["tema"]).'">'.$datosTotalRelacionados["tema"].'</a></li>';
			break;

			case '5':// parcialmente EQ
			$ieq=++$ieq;
			$row_EQ.='          <li>'.$td_delete.' <abbr class="thesacronym" title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr> ';
			$row_EQ.='          <a title="'.LABEL_verDetalle.' '.$datosTotalRelacionados["tema"].' ('.LABEL_termino_parcial_equivalente.')"  href="'.URL_BASE.'index.php?tema='.$datosTotalRelacionados[tema_id].'&amp;/'.string2url($datosTotalRelacionados["tema"]).'">'.$datosTotalRelacionados["tema"].'</a> ('.$datosTotalRelacionados["titulo"].')</li>';
			break;

			case '6':// EQ
			$ieq=++$ieq;
			$row_EQ.='          <li>'.$td_delete.' <abbr class="thesacronym" title="'.LABEL_termino_equivalente.'" lang="'.LANG.'">'.EQ_acronimo.'</abbr> ';
			$row_EQ.='          <a title="'.LABEL_verDetalle.' '.$datosTotalRelacionados["tema"].' ('.LABEL_termino_equivalente.')"  href="'.URL_BASE.'index.php?tema='.$datosTotalRelacionados[tema_id].'&amp;/'.string2url($datosTotalRelacionados["tema"]).'">'.$datosTotalRelacionados["tema"].'</a> ('.$datosTotalRelacionados["titulo"].')</li>';
			break;

			case '7':// NO EQ
			$ieq=++$ieq;
			$row_EQ.='          <li>'.$td_delete.' <abbr class="thesacronym" title="'.LABEL_termino_no_equivalente.'" lang="'.LANG.'">'.NEQ_acronimo.'</abbr> ';
			$row_EQ.='          <a title="'.LABEL_verDetalle.' '.$datosTotalRelacionados["tema"].' ('.LABEL_termino_no_equivalente.')"  href="'.URL_BASE.'index.php?tema='.$datosTotalRelacionados[tema_id].'&amp;/'.string2url($datosTotalRelacionados["tema"]).'">'.$datosTotalRelacionados["tema"].'</a> ('.$datosTotalRelacionados["titulo"].')</li>';
			break;
		}
	};

	//Si no es un término válido y es un UF.
	if(SQLcount($sqlTotalRelacionados)==0){

		$sqlTerminosValidosUF=SQLterminosValidosUF($idTema);

		while($arrayTerminosValidosUF= $sqlTerminosValidosUF->FetchRow()){

			//Reasignación del tema_id para utilizar en fuentes XML y como tema_id canónico
			$tema_id=$arrayTerminosValidosUF["tema_pref_id"];

			switch($arrayTerminosValidosUF["t_relacion"]){

				case '4':// USE
				$leyendaConector=USE_termino;
					$iuse=++$iuse;
					$row_USE.='<li><em>'.$arrayTerminosValidosUF["tema"].'</em> '.$leyendaConector.' <a title="'.LABEL_verDetalle.$arrayTerminosValidosUF[tema_pref].'" href="'.URL_BASE.'index.php?tema='.$arrayTerminosValidosUF[tema_pref_id].'">'.$arrayTerminosValidosUF["tema_pref"].'</a> </li>'."\r\n" ;
				break;


				case '5':// parcialmente EQ
				$leyendaConector='<abbr class="thesacronym" title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr>';
				$ieq=++$ieq;
				$row_EQ='<li><em>'.$arrayTerminosValidosUF["tema"].'</em> ('.$arrayTerminosValidosUF["titulo"].' / '.$arrayTerminosValidosUF[idioma].') '.$leyendaConector.' <a title="'.LABEL_verDetalle.$arrayTerminosValidosUF[tema_pref].'" href="'.URL_BASE.'index.php?tema='.$arrayTerminosValidosUF["tema_pref_id"].'">'.$arrayTerminosValidosUF["tema_pref"].'</a> </li>'."\r\n" ;
				break;

				case '6':// EQ
				$leyendaConector='<abbr class="thesacronym" title="'.LABEL_termino_equivalente.'" lang="'.LANG.'">'.EQ_acronimo.'</abbr>';
				$ieq=++$ieq;
				$row_EQ='<li><em>'.$arrayTerminosValidosUF["tema"].'</em> ('.$arrayTerminosValidosUF["titulo"].' / '.$arrayTerminosValidosUF[idioma].') '.$leyendaConector.' <a title="'.LABEL_verDetalle.$arrayTerminosValidosUF[tema_pref].'" href="'.URL_BASE.'index.php?tema='.$arrayTerminosValidosUF["tema_pref_id"].'">'.$arrayTerminosValidosUF["tema_pref"].'</a> </li>'."\r\n" ;
				break;

				case '7':// NO EQ
				$leyendaConector='<abbr class="thesacronym" title="'.LABEL_termino_no_equivalente.'" lang="'.LANG.'">'.NEQ_acronimo.'</abbr>';
				$ieq=++$ieq;
				$row_EQ='<li><em>'.$arrayTerminosValidosUF["tema"].'</em> ('.$arrayTerminosValidosUF["titulo"].' / '.$arrayTerminosValidosUF[idioma].') '.$leyendaConector.' <a title="'.LABEL_verDetalle.$arrayTerminosValidosUF[tema_pref].'" href="'.URL_BASE.'index.php?tema='.$arrayTerminosValidosUF["tema_pref_id"].'">'.$arrayTerminosValidosUF["tema_pref"].'</a> </li>'."\r\n" ;
				break;

			}
		};
	}

	$rows=array("UP"=>doListaTag($iuf,'ul',$row_UP,"UP","list-unstyled"),
	"USE"=>doListaTag($iuse,'ul',$row_USE,"EQ","list-unstyled"),
	"TR"=>doListaTag($irt,'ul',$row_TR,"TR","list-unstyled"),
	"TG"=>doListaTag($itg,'ul',$row_TG,"TG","list-unstyled"),
	"TE"=>doListaTag($int,'ul',$row_NT,"TE","list-unstyled"),
	"EQ"=>doListaTag($ieq,'ul',$row_EQ,"EQ","list-unstyled")
);

$cant_relaciones=array(
	"cantUF"=>$iuf,
	"cantRT"=>$irt,
	"cantTG"=>$itg,
	"cantNT"=>$int,
	"cantEQ"=>$ieq,
	"cantTotal"=>$iuf+$irt+$itg+$int+$iuse+$ieq
);

return array("HTMLterminos"=>$rows,
"cantRelaciones"=>$cant_relaciones,
"tema_id"=>$tema_id);
};
#######################################################################



function HTMLmenuCustumRel($tema_id,$arrayDataRelation)
{

	$arrayLabel[2]=array(TR_acronimo,TR_termino);
	$arrayLabel[3]=array(TG_acronimo,TG_termino);
	$arrayLabel[4]=array(UP_acronimo,UP_termino);

	if($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"])
	{
		$rows.='<abbr class="editable_select'.$arrayDataRelation[t_relacion].'" id="edit_rel_id'.$arrayDataRelation[rel_id].'" style="display: inline" title="'.$arrayLabel["$arrayDataRelation[t_relacion]"][1].' '.$arrayDataRelation[rr_value].'" lang="'.LANG.'">'.$arrayLabel["$arrayDataRelation[t_relacion]"][0].$arrayDataRelation[rr_code].'</abbr>';
	}
	else
	{
		$rows.='<abbr class="editable_select'.$arrayDataRelation[t_relacion].'" id="r'.$arrayDataRelation[rel_id].'" style="display: inline" title="'.$arrayLabel["$arrayDataRelation[t_relacion]"][1].' '.$arrayDataRelation[rr_value].'" lang="'.LANG.'">'.$arrayLabel["$arrayDataRelation[t_relacion]"][0].$arrayDataRelation[rr_code].'</abbr>';
	}

	return $rows;
}




//home page for term
function HTMLbodyTermino($array){

	GLOBAL $MSG_ERROR_RELACION;
	GLOBAL $CFG;

	$editFlag=($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]) ? 1 : 0;

	$sqlMiga=SQLarbolTema($array["idTema"]);

	$cantBT=SQLcount($sqlMiga);

	$i_profundidad=($cantBT>0) ? $cantBT : 1;

	$HTMLterminos=doContextoTermino($array["idTema"],$i_profundidad);

	$fecha_crea=do_fecha($array["cuando"]);
	$fecha_estado=do_fecha($array["cuando_estado"]);


	//Si tiene padres
	if($cantBT>0){
		while($arrayMiga=$sqlMiga->FetchRow()){

			if($arrayMiga["tema_id"]!==$array["idTema"]){
				$menu_miga.='<li><a title="'.LABEL_verDetalle.$arrayMiga["tema"].'" href="'.URL_BASE.'index.php?tema='.$arrayMiga["tema_id"].'&amp;/'.string2url($arrayMiga["tema"]).'" >'.$arrayMiga["tema"].'</a></li>';
			}
		}
	};

	$row_miga.='<ol class="breadcrumb"><li><a title="'.MENU_Inicio.'" href="'.URL_BASE.'index.php">'.ucfirst(MENU_Inicio).'</a></li>'.$menu_miga.'<li>'.$array["titTema"].'</li></ol>';

	$body='<div class="container" id="bodyText">';

	//MENSAJE DE ERROR
	$body.=$MSG_ERROR_RELACION;

	if($array["isMetaTerm"]==1)	{
		$body.=' <h1 class="metaTerm" title="'.$array["titTema"].' - '.NOTE_isMetaTermNote.'" id="T'.$array["tema_id"].'">'.$array["titTema"].'</h1>';
		$body.=' <p class="metaTerm alert" title="'.NOTE_isMetaTermNote.'" id="noteT'.$array[tema_id].'">'.NOTE_isMetaTerm.'</p>';
	}	else	{
		$body.=' <h1 class="estado_termino'.$array["estado_id"].'">'.$array["titTema"].'</h1>';
	}
	//div oculto para eliminar término
	if($editFlag==1)	{
		$body.=HTMLconfirmDeleteTerm($array);
	}


	#Div miga de pan
	$body.='<div id="breadScrumb">';
	$body.=$row_miga;
	$body.='</div>';
	# fin Div miga de pan
	$cantNotas=count($array["notas"]);
	$body.='<ul id="myTermTab" class="nav nav-tabs" style="margin-bottom: 15px;"><li ><a class="active" href="#theTerm" data-toggle="tab">'.ucfirst(LABEL_Termino).'</a></li>';

	if($cantNotas>0) {
		$body.='<li><a href="#notesTerm" id="labelNotes" data-toggle="tab">'.ucfirst(LABEL_notes).' <span class="badge">'.$cantNotas.'</span></a></li>';
	}

	//term menu
	if($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"])	{
		$body.=HTMLtermMenuX2($array,$HTMLterminos["cantRelaciones"]);
	}

	$body.='<li><a href="#metadataTerm" data-toggle="tab">'.ucfirst(LABEL_metadatos).'</a></li>';
	$body.='    </ul>';

	#Tabs content
	$body.='<div id="tabContent" class="tab-content">';

	$body.='<div class="tab-pane fade" id="notesTerm">';
	$body.=HTMLNotasTermino($array,$editFlag);
	$body.='</div>';
	#Div relaciones del terminos
	$body.='<div class="tab-pane fade in active" id="theTerm">';

		//el termino //span editable
	if($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]>0){
		$body.='<h3 class="term "id="term"><span id="edit_tema'.$array["tema_id"].'" class="edit_area_term">'.$array["titTema"].'</span></h3> ' ;
	} else{
		$body.='<h3  class="termDefinition" data-content="'.TXTtermDefinition($array,array($_SESSION[$_SESSION["CFGURL"]]["_GLOSS_NOTES"])).'" rel="popover" data-placement="top" data-trigger="hover">'.$array["titTema"].'</h3>';
	}




	$body.=HTMLshowCode($array);

	if($HTMLterminos["cantRelaciones"]["cantUF"]>0) {
		$body.='<h4>'.ucfirst(LABEL_nonPreferedTerms).'</h4>';
		$body.='<div>'.$HTMLterminos["HTMLterminos"]["UP"].'</div>';
	}
	if($HTMLterminos["cantRelaciones"]["cantTG"]>0) {
		$body.='<h4>'.ucfirst(LABEL_broatherTerms).'</h4>';
		$body.='<div>'.$HTMLterminos["HTMLterminos"]["TG"].'</div>';
	}
	//display terms relations
	$body.=$HTMLterminos["HTMLterminos"]["USE"];

	if($HTMLterminos["cantRelaciones"]["cantNT"]>0) {
		$body.='<h4>'.ucfirst(LABEL_narrowerTerms).'</h4>';
		$body.='<div>'.$HTMLterminos["HTMLterminos"]["TE"].'</div>';
	}

	if($HTMLterminos["cantRelaciones"]["cantRT"]>0) {
		$body.='<h4>'.ucfirst(LABEL_relatedTerms).'</h4>';
		$body.='<div>'.$HTMLterminos["HTMLterminos"]["TR"].'</div>';
	}


	if($HTMLterminos["cantRelaciones"]["cantEQ"]>0) {
		$body.=$HTMLterminos["HTMLterminos"]["EQ"];
		}

	$body.=HTMLtargetTerms($array["tema_id"]);

	$body.=HTMLURI4term($array["tema_id"]);
	# fin Div relaciones del terminos
	$body.='    </div>';


	#Div pie de datos
	$body.='<div class="tab-pane fade" id="metadataTerm">';
	$body.=HTMLtermMetadata($array,$HTMLterminos["cantRelaciones"]);
	$body.='</div>';



	$body.='</div>';#Tabs content

	$body.='</div>';	#Fin div bodyText

	return $body;
};



function HTMLmainMenu(){

	$row.='<ul class="nav navbar-nav navbar-right">';
	$row.='<li class="dropdown">
	<a href="#" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-toggle="dropdown">'.ucfirst(LABEL_Menu).' <b class="caret"></b></a>';

	$row.='<ul class="dropdown-menu">';
	/*
	* Admin menu
	*/
	if($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"]=='1'){
		$row.='<li class="dropdown dropdown-submenu"><a href="#" class="dropdown-toggle" data-toggle="dropdown">'.LABEL_Admin.'</a>';
		$row.='<ul class="dropdown-menu">';
		$row.='<li><a title="'.ucfirst(LABEL_lcConfig).'" href="admin.php?vocabulario_id=list">'.ucfirst(LABEL_lcConfig).'</a></li>';
		$row.='<li><a title="'.ucfirst(MENU_bulkEdition).'" href="admin.php?doAdmin=bulkReplace">'.ucfirst(MENU_bulkEdition).'</a></li>';
		$row.='<li><a title="'.ucfirst(MENU_glossConfig).'" href="admin.php?doAdmin=glossConfig">'.ucfirst(MENU_glossConfig).'</a></li>';
		$row.='<li><a title="'.ucfirst(MENU_Usuarios).'" href="admin.php?user_id=list">'.ucfirst(MENU_Usuarios).'</a></li>';
		$row.='<li><a title="'.ucfirst(LABEL_export).'" href="admin.php?doAdmin=export">'.ucfirst(LABEL_export).'</a></li>';

		$row.='<li class="dropdown dropdown-submenu"><a href="#" class="dropdown-toggle" data-toggle="dropdown">'.ucfirst(LABEL_dbMantenimiento).'</a>
		<ul class="dropdown-menu">';
		$row.='<li><a href="admin.php?doAdmin=reindex">'.ucfirst(LABEL_reIndice).'</a></li>';

		//Enable or not SPARQL endpoint
		$row.=(CFG_ENABLE_SPARQL==1) ? '<li><a href="admin.php?doAdmin=updateEndpoint">'.ucfirst(LABEL_updateEndpoint).'</a></li>' :'';

		$row.='<li><a href="admin.php?doAdmin=import" title="'.ucfirst(LABEL_import).'">'.ucfirst(LABEL_import).'</a></li>';
		$row.='<li><a href="admin.php?doAdmin=massiverem" title="'.ucfirst(MENU_massiverem).'">'.ucfirst(MENU_massiverem).'</a></li>';
		$row.='<li><a title="'.ucfirst(MENU_DatosTesauro).'" href="admin.php?opTbl=TRUE">'.ucfirst(LABEL_OptimizarTablas).'</a></li>';
		$row.='<li><a title="'.ucfirst(LABEL_update1_6x1_7).'" href="admin.php?doAdmin=updte1_6x1_7">'.ucfirst(LABEL_update1_6x1_7).'</a></li>';
		$row.='<li><a title="'.ucfirst(LABEL_update1_5x1_6).'" href="admin.php?doAdmin=updte1_5x1_6">'.ucfirst(LABEL_update1_5x1_6).'</a></li>';
		$row.='<li><a title="'.ucfirst(LABEL_update1_4x1_5).'" href="admin.php?doAdmin=updte1_4x1_5">'.ucfirst(LABEL_update1_4x1_5).'</a></li>';
		$row.='<li><a title="'.ucfirst(LABEL_update1_3x1_4).'" href="admin.php?doAdmin=updte1_3x1_4">'.ucfirst(LABEL_update1_3x1_4).'</a></li>';
		$row.='<li><a title="'.ucfirst(LABEL_update1_1x1_2).'" href="admin.php?doAdmin=updte1_1x1_2">'.ucfirst(LABEL_update1_1x1_2).'</a></li>';
		$row.='<li><a title="'.ucfirst(LABEL_update1x1_2).'" href="admin.php?doAdmin=updte1x1_2">'.ucfirst(LABEL_update1x1_2).'</a></li>';

		$row.='</ul></li>';

		$row.='</ul></li>';
	}

	/*
	Menu ver
	*/
	$row.='<li class="dropdown dropdown-submenu"><a href="#" class="dropdown-toggle" data-toggle="dropdown">'.ucfirst(LABEL_Ver).'</a><ul class="dropdown-menu">';

	$row.='<li><a title="'.ucfirst(LABEL_terminosLibres).'" href="'.URL_BASE.'index.php?verT=L">'.ucfirst(LABEL_terminosLibres).'</a></li>';
	$row.='<li><a title="'.ucfirst(LABEL_terminosRepetidos).'" href="'.URL_BASE.'index.php?verT=R">'.ucfirst(LABEL_terminosRepetidos).'</a></li>';
	$row.='<li><a title="'.ucfirst(LABEL_termsNoBT).'" href="'.URL_BASE.'index.php?verT=NBT">'.ucfirst(LABEL_termsNoBT).'</a></li>';
	$row.='<li><a title="'.ucfirst(LABEL_Rechazados).'" href="'.URL_BASE.'index.php?estado_id=14">'.ucfirst(LABEL_Rechazados).'</a></li>';
	$row.='<li><a title="'.ucfirst(LABEL_Candidato).'" href="'.URL_BASE.'index.php?estado_id=12">'.ucfirst(LABEL_Candidatos).'</a></li>';
	$row.='</ul></li>';


	//User menu
	$row.='<li><a title="'.LABEL_bulkTranslate.'" href="'.URL_BASE.'index.php?mod=trad">'.ucfirst(MENU_bulkTranslate).'</a></li>';
	$row.='<li><a title="'.LABEL_FORM_simpleReport.'" href="'.URL_BASE.'index.php?mod=csv">'.LABEL_FORM_simpleReport.'</a></li>';
	$row.='<li><a title="'.MENU_MisDatos.'" href="login.php">'.MENU_MisDatos.'</a></li>';
	$row.='<li><a title="'.MENU_Salir.'" href="'.URL_BASE.'index.php?cmdlog='.substr(md5(date("Ymd")),"5","10").'">'.MENU_Salir.'</a></li>';

	$row.='   </ul>';
	$row.='</li>';

	$row.='<li><a role="button" title="'.ucfirst(MENU_AgregarT).'" href="'.URL_BASE.'index.php?taskterm=addTerm&amp;tema=0">'.ucfirst(MENU_AgregarT).'</a></li>';


	$row.='</ul>';



	return $row;


};




#
# term menu options
#
function HTMLtermMenuX2($array_tema,$relacionesTermino){

	$sqlcheckIsValidTerm=SQLcheckIsValidTerm($array_tema["tema_id"]);
	$isValidTerm=(SQLcount($sqlcheckIsValidTerm)==0) ? true : false;

	// PERMITIR O NO POLIJERARQUIAS//
	if( ( ($relacionesTermino["cantTG"]==0) || ($_SESSION["CFGPolijerarquia"]=='1') ) &&
		($array_tema["estado_id"]=='13') &&
		($isValidTerm)){
			$link_subordinar='<li><a title="'.MENU_AgregarTG.'" href="'.URL_BASE.'index.php?taskterm=addBT&amp;tema='.$array_tema["idTema"].'">'.ucfirst(MENU_AgregarTG).'</a></li>';
		};

	$row.='<li><a href="#" class="dropdown-toggle" data-toggle="dropdown">'.ucfirst(LABEL_Opciones).'<b class="caret"></b></a>';
	$row.='<ul class="dropdown-menu" role="menu">';

	if(($relacionesTermino["cantNT"]+$relacionesTermino["cantUF"])==0)
	//no have relations
	if(($relacionesTermino["cantTotal"])=='0')
	{
		/*
		Change status term
		*/
		$link_estado.='<li class="dropdown-submenu" role="menu"> <a tabindex="0" label-primary data-toggle="dropdown">'.ucfirst(LABEL_CambiarEstado).'</a><ul class="dropdown-menu" id="menu_estado">';
		switch($array_tema["estado_id"]){
			case '12':
			//Candidato / candidate => aceptado
			$link_estado.='<li><a title="'.LABEL_AceptarTermino.'" href="'.URL_BASE.'index.php?tema='.$array_tema["idTema"].'&amp;estado_id=13">'.ucfirst(LABEL_AceptarTermino).'</a></li>';
			//Candidato / candidate => rechazado
			$link_estado.='<li><a title="'.LABEL_RechazarTermino.'" href="'.URL_BASE.'index.php?tema='.$array_tema["idTema"].'&amp;estado_id=14">'.ucfirst(LABEL_RechazarTermino).'</a></li>';
			break;

			case '13':
			//Aceptado / Acepted=> Rechazado
			$link_estado.='<li><a title="'.LABEL_RechazarTermino.'" href="'.URL_BASE.'index.php?tema='.$array_tema["idTema"].'&amp;estado_id=14">'.ucfirst(LABEL_RechazarTermino).'</a></li>';
			break;

			case '14':
			//Rechazado / Rejected=> Candidato
			$link_estado.='<li><a title="'.LABEL_CandidatearTermino.'" href="'.URL_BASE.'index.php?tema='.$array_tema["idTema"].'&amp;estado_id=12">'.ucfirst(LABEL_CandidatearTermino).'</a></li>';
			break;
		}
		$link_estado.='</ul></li>';
	};


	$row.='      <li><a title="'.MENU_EditT.'" href="'.URL_BASE.'index.php?taskterm=editTerm&amp;tema='.$array_tema["idTema"].'">'.ucfirst(MENU_EditT).'</a></li>';

	//If the term are not accepted and do not have Add-menu => add notes options here!

	if($array_tema["estado_id"]!=="13"){
		$row.='     <li><a title="'.ucfirst(LABEL_EditorNota).'" href="'.URL_BASE.'index.php?taskterm=editNote&amp;note_id=?&amp;editNota=?&amp;tema='.$array_tema["idTema"].'">'.ucfirst(LABEL_EditorNota).'</a></li>';
		}

	$row.=$link_subordinar;

	$row.=$link_estado;

	if($isValidTerm){
		if($array_tema["isMetaTerm"]==1)		{
			$label_task_meta_term=LABEL_turnOffMetaTerm;
			$task_meta_term=0;
		}		else		{
			$label_task_meta_term=LABEL_turnOnMetaTerm;
			$task_meta_term=1;
		}

		$row.='<li><a title="'.$label_task_meta_term.'" href="'.URL_BASE.'index.php?taskterm=metaTerm&amp;mt_status='.$task_meta_term.'&amp;tema='.$array_tema["idTema"].'">'.ucfirst($label_task_meta_term).'</a></li>';
	}


	$row.='<li><a class="btn btn-danger" title="'.LABEL_EliminarTE.'" href="javascript:expandLink(\'borrart\')">'.ucfirst(LABEL_EliminarTE).'</a></li>';
	$row.='</ul>';

	$row.='</li><!-- end menu -->';

	$row.='<li><a href="#" class="dropdown-toggle"  role="menu" data-toggle="dropdown">'.ucfirst(LABEL_Agregar).'<b class="caret"></b></a>';
	$row.='<ul class="dropdown-menu" id="menu_agregar">';

	$row.='<li><a title="'.ucfirst(LABEL_nota).'" href="'.URL_BASE.'index.php?taskterm=editNote&amp;note_id=?&amp;editNota=?&amp;tema='.$array_tema["idTema"].'">'.ucfirst(LABEL_nota).'</a></li>';

	$row.='<li role="separator" class="divider"></li>';


	//solo acepta relaciones si el término esta aceptado
	if(($array_tema["estado_id"]=='13') && ($isValidTerm)){

		//link agregar un UP
		$row.='     <li><a title="'.MENU_AgregarUP.'" href="'.URL_BASE.'index.php?taskterm=addUF&amp;tema='.$array_tema["idTema"].'">'.ucfirst(MENU_AgregarUP).'</a></li>';

		//link agregar un TE
		$row.='     <li><a title="'.MENU_AgregarTE.'" href="'.URL_BASE.'index.php?taskterm=addNT&amp;tema='.$array_tema["idTema"].'">'.ucfirst(MENU_AgregarTE).'</a></li>';

		//link agregar un TR
		$row.='     <li><a title="'.MENU_AgregarTR.'" href="'.URL_BASE.'index.php?taskterm=addRTnw&amp;tema='.$array_tema["idTema"].'">'.ucfirst(MENU_AgregarTR).'</a></li>';

		$row.='     <li><a title="'.LABEL__getForRecomendation.'" href="'.URL_BASE.'index.php?taskterm=findSuggestionTargetTerm&amp;tema='.$array_tema["idTema"].'">'.ucfirst(LABEL__getForRecomendation).'</a></li>';

		$row.='<li role="separator" class="divider"></li>';

		$row.='     <li><a title="'.LABEL_URI2term.'" href="'.URL_BASE.'index.php?taskterm=addURI&amp;tema='.$array_tema["idTema"].'">'.ucfirst(LABEL_URI2term).'</a></li>';

		$row.='<li class="dropdown-submenu">
		<a tabindex="0" data-toggle="dropdown">'.ucfirst(LABEL_relbetweenVocabularies).'</a>
		<ul class="dropdown-menu" role="menu" id="menu_agregar_relaciones">';
		//link agregar un EQ
		$row.='     <li><a title="'.LABEL_vocabulario_referencia.'" href="'.URL_BASE.'index.php?taskterm=addEQ&amp;tema='.$array_tema["idTema"].'">'.ucfirst(LABEL_vocabulario_referencia).'</a></li>';
		//link agregar un término externo vía web services
		$row.='     <li><a title="'.LABEL_relacion_vocabularioWebService.'" href="'.URL_BASE.'index.php?taskterm=findTargetTerm&amp;tema='.$array_tema["idTema"].'">'.ucfirst(LABEL_vocabulario_referenciaWS).'</a></li>';
		$row.='    </ul>';
		$row.='</li>';
		}//fin control de estado y validez

		$row.='</ul>';

		$row.='</li>';


	return $row;
};



#
# Ficha del término
#
function HTMLNotasTermino($array,$editFlag=0){

	if(count($array["notas"])){
		for($iNota=0; $iNota<(count($array["notas"])); ++$iNota){
			if($array["notas"][$iNota][id]){
				$body.='<div class="NA" id="'.$array["notas"][$iNota]["tipoNota"].$array["notas"][$iNota]["id"].'">';
				$body.='<dl id="notas">';
				switch($array["notas"][$iNota]["tipoNota"]){
					case 'NA';
					$tipoNota=LABEL_NA;
					break;

					case 'NH';
					$tipoNota=LABEL_NH;
					break;

					case 'NC';
					$tipoNota=LABEL_NC;
					break;

					case 'NB';
					$tipoNota=LABEL_NB;
					break;

					case 'NP';
					$tipoNota=LABEL_NP;
					break;

				}

				$tipoNota=(in_array($array["notas"][$iNota]["tipoNota_id"],array(8,9,10,11,15))) ? arrayReplace(array(8,9,10,11,15),array(LABEL_NA,LABEL_NH,LABEL_NB,LABEL_NP,LABEL_NC),$array[notas][$iNota][tipoNota_id]) : $array[notas][$iNota][tipoNotaLabel];
				//idioma de la nota
				//Rellenar si esta vacion
				$array["notas"][$iNota]["lang_nota"]=(!$array["notas"][$iNota]["lang_nota"]) ? $_SESSION["CFGIdioma"] : $array["notas"][$iNota]["lang_nota"];

				//no mostrar si es igual al idioma del vocabulario
				$label_lang_nota=($array["notas"][$iNota]["lang_nota"]==$_SESSION["CFGIdioma"]) ? '' : ' ('.$array["notas"][$iNota]["lang_nota"].')';

				//enable or not edit
				if($editFlag==1){
					$body.='<dt> <a title="'.LABEL_EditarNota.'" href="'.URL_BASE.'index.php?editNota='.$array["notas"][$iNota]["id"].'&amp;taskterm=editNote&amp;tema='.$array["idTema"].'">'.$tipoNota.'</a>'.$label_lang_nota;
					$body.=' <a role="button" class="btn btn-primary btn-xs" href="'.URL_BASE.'index.php?editNota='.$array["notas"][$iNota]["id"].'&amp;taskterm=editNote&amp;tema='.$array["idTema"].'">'.ucfirst(LABEL_EditarNota).'</a>';
					$body.=' <a role="button" class="btn btn-danger btn-xs" href="'.URL_BASE.'index.php?tema='.$array["idTema"].'&amp;idTema='.$array["idTema"].'&amp;idNota='.$array["notas"][$iNota]["id"].'&amp;taskNota=rem" name="eliminarNota" title="'.LABEL_EliminarNota.'"/>'.ucfirst(LABEL_EliminarNota).'</a>';
					$body.='</dt>';
					//$body.='<dd> '.wiki2html($array["notas"][$iNota]["nota"]);
					$body.='<dd> '.wiki2link($array["notas"][$iNota]["nota"]);
					$body.='<div class="footnote">'.$array["notas"][$iNota]["cuando_nota"].' <a href="'.URL_BASE.'sobre.php?user_id='.$array["notas"][$iNota]["user_id"].'#termaudit" title="'.LABEL_DatosUser.'">'.$array["notas"][$iNota]["user"].'</a></div>';
					$body.='<div class="footnote"></div>';
					$body.='</dd>';
				}else{
					$body.='<dt>'.$tipoNota.$label_lang_nota.'</dt><dd> '.wiki2html($array["notas"][$iNota]["nota"]).'</dd>';
				}

				$body.='</dl>';;
				$body.='</div>';;
			};//fin de if id nota
		};// fin del for

	};
	return $body;
};

#
# //// BUCLE HACIA ARRIBA
#
function sql_rel($idTema,$i){
	if($i<10){// Para evitar bucle infinito en caso de error por relaciones recursivas. max= 10
		$sql=SQLbucleArriba($idTema);
		if(SQLcount($sql)=='0'){
			$datos=ARRAYverTerminoBasico($idTema);
			if($i==0){
				$enlace.='#'.$datos[titTema].' ';
			}else{
				$enlace.='#<a title="'.LABEL_verDetalle.$datos[titTema].'" href="'.URL_BASE.'index.php?tema='.$datos[idTema].'">'.$datos[titTema].'</a>';
			};

			return $enlace;
		}else{
			return ver_txt($sql,$i);
		};
	};
};



#
# //// PROCESAMIENTO HTML DEL BUCLE HACIA ARRIBA
#

function ver_txt($datos,$i){
	while($lista=$datos->FetchRow()){
		if($i==0){
			$rows.='#'.$lista[2].'';
		}else{
			$rows.='#<a title="'.LABEL_verDetalle.$lista[2].'" href="'.URL_BASE.'index.php?tema='.$lista[1].'">'.$lista[2].'</a>';
		}

		if($lista[0]>0){
			$rows.=sql_rel($lista[0],$i+1);
		}
	};

	return $rows;
};



#
# //// armado de un tema_id acumula los tema_ids hacia arriba
#
function bucle_arriba($indice_temas,$idTema){
	$sql=SQLbucleArriba($idTema);
	$indice_temas.=otro_bucle_arriba($sql);
	return $indice_temas;
};

function otro_bucle_arriba($sql){
	while($lista=$sql->FetchRow()){
		if($lista[0]>0){
			$indice_temas.=bucle_arriba($lista[0].'|',$lista[0]);
		}
	}

	return $indice_temas;
};
#
# //// BUCLE HACIA ABAJO
#
function evalRelacionSuperior($idTema,$i,$idTemaEvaluado){

	$sql=SQLbucleArriba($idTema);

	if(SQLcount($sql)==0)
	{
		return "TRUE";
	}
	else
	{
		return evalSubordina($sql,$i,$idTemaEvaluado);
	};
};


#
# //// PROCESAMIENTO ARRAY DEL BUCLE HACIA abajo
#
function evalSubordina($datos,$i,$idTemaEvaluado){

	while($lista=$datos->FetchRow()){
		if(($idTemaEvaluado==$lista[1])||($idTemaEvaluado==$lista[0])){
			return FALSE;
		}elseif($lista[0]>1){
			return evalRelacionSuperior($lista[0],$i+1,$idTemaEvaluado);
		}else{
			return TRUE;
		}
	};
};



#
# Armado del menú de cambio de idioma
#
function doMenuLang($tema_id="0"){

	GLOBAL $idiomas_disponibles;

	$selectLang='<form id="select-lang" method="get" action="index.php">';
	$selectLang.='<select class="navbar-btn btn-info btn-sx pull-right" name="setLang" id="setLang" onchange="this.form.submit();">';
	foreach ($idiomas_disponibles AS $key => $value) {
		if($value[2]==$_SESSION[$_SESSION["CFGURL"]]["lang"][2]){
			$selectLang.='<option value="'.$value[2].'" selected="selected">'.$value[0].'</option>';
		}else{
			$selectLang.='<option value="'.$value[2].'">'.$value[0].'</option>';
		};
	};

	$selectLang.='</select>';

	if((is_numeric($tema_id)) && ($tema_id>0))
	{
		$selectLang.='<input type="hidden" name="tema" value="'.$tema_id.'" />';
	}
	$selectLang.='</form>';

	return $selectLang;
};




#
# Armado de tabla de términos según meses
#
function doBrowseTermsFromDate($month,$year,$ord=""){

	GLOBAL $MONTHS;
	$sql=SQLlistTermsfromDate($month,$year,$ord);
	$rows.='<div class="table-responsive"> ';
	$rows.='<table id="termaudit" class="table table-striped table-bordered table-condensed table-hover" summary="'.LABEL_auditoria.'" >';
	$rows.='<tbody>';
	while($array=$sql->FetchRow()){
		$fecha_termino=do_fecha($array[cuando]);

		$rows.='<tr>';
		$rows.='<td class="izq">'.HTMLlinkTerm($array,array("modal"=>1)).'</td>';
		$rows.='<td>'.$fecha_termino[dia].' / '.$fecha_termino[descMes].' / '.$fecha_termino[ano].'</td>';
		if($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"]=='1'){
			$rows.='<td><a href="admin.php?user_id='.$array[id_usuario].'" title="'.LABEL_DatosUser.'">'.$array[apellido].', '.$array[nombres].'</a></td>';
		}else{
			$rows.='<td>'.$array[apellido].', '.$array[nombres].'</td>';
		}
		$rows.='</tr>';
	};
	$rows.='</tbody>';

	$rows.='<thead>';
	$rows.='<tr>';
	$rows.='<th class="izq" colspan="3"><a href="'.URL_BASE.'sobre.php#termaudit" title="'.ucfirst(LABEL_auditoria).'">'.ucfirst(LABEL_auditoria).'.</a> &middot; '.$fecha_termino[descMes].' / '.$fecha_termino[ano].': '.SQLcount($sql).' '.LABEL_Terminos.'</th>';
	$rows.='</tr>';

	$rows.='<tr>';
	$rows.='<th><a href="'.URL_BASE.'sobre.php?m='.$month.'&y='.$year.'&ord=T#termaudit" title="'.LABEL_ordenar.' '.LABEL_Termino.'">'.ucfirst(LABEL_Termino).'</a></th>';
	$rows.='<th><a href="'.URL_BASE.'sobre.php?m='.$month.'&y='.$year.'&ord=F#termaudit" title="'.LABEL_ordenar.' '.LABEL_Fecha.'">'.ucfirst(LABEL_Fecha).'</a></th>';
	$rows.='<th><a href="'.URL_BASE.'sobre.php?m='.$month.'&y='.$year.'&ord=U#termaudit" title="'.LABEL_ordenar.' '.MENU_Usuarios.'">'.ucfirst(MENU_Usuarios).'</a></th>';
	$rows.='</tr>';
	$rows.='</thead>';


	$rows.='<tfoot>';
	$rows.='<tr>';
	$rows.='<td class="izq">'.ucfirst(LABEL_TotalTerminos).'</td>';
	$rows.='<td colspan="2">'.SQLcount($sql).'</td>';
	$rows.='</tr>';
	$rows.='</tfoot>';

	$rows.='</table>        ';
	$rows.='</div>        ';
	return $rows;
};


#
# Armado de browse de términos
#
function doBrowseTermsByDate(){
	GLOBAL $MONTHS;
	$sql=SQLtermsByDate();
	$rows.='<div class="table-responsive"> ';
	$rows.='<table id="termaudit" class="table table-striped table-bordered table-condensed table-hover" summary="'.ucfirst(LABEL_auditoria).'">';

	$rows.='<thead>';

	$rows.='<tr>';
	$rows.='<th colspan="3" class="titulo_tabla">'.ucfirst(LABEL_auditoria).'</th>';
	$rows.='</tr>';

	$rows.='<tr>';
	$rows.='<th>'.ucfirst(LABEL_ano).'</th>';
	$rows.='<th>'.ucfirst(LABEL_mes).'</th>';
	$rows.='<th>'.ucfirst(LABEL_Terminos).'</th>';
	$rows.='</tr>';
	$rows.='</thead>';


	$rows.='<tbody>';
	while($array=$sql->FetchRow()){

		$fecha_termino=do_fecha($array[cuando]);
		$rows.='<tr>';
		$rows.='<td class="centrado">'.$array[years].'</td>';
		$rows.='<td class="centrado"><a href="'.URL_BASE.'sobre.php?m='.$array[months].'&y='.$array[years].'#termaudit" title="'.LABEL_verDetalle.$fecha_termino[descMes].'">'.$fecha_termino[descMes].'</a></td>';
		$rows.='<td class="centrado">'.$array["cant"].'</td>';
		$rows.='</tr>';
		$TotalTerminos+=$array["cant"];
	};
	$rows.='</tbody>';
	$rows.='<tfoot>';
	$rows.='<tr>';
	$rows.='<td colspan="2">'.ucfirst(LABEL_TotalTerminos).'</td>';
	$rows.='<td>'.$TotalTerminos.'</td>';
	$rows.='</tr>';
	$rows.='</tfoot>';

	$rows.='</table>        ';
	$rows.='</div>        ';
	return $rows;
};



function HTML_URLsearch($display=Array(),$arrayTema=Array()) {

	GLOBAL $CFG;
	$ARRAY_busquedas=$CFG["SEARCH_URL_SITES_SINTAX"];

	$string_busqueda = $arrayTema[titTema];
	$html = '<ul class="list-inline" id="enlaces_web">' . "\n";
	foreach($display as $sitename) {
		if (in_array($sitename, $ARRAY_busquedas))
		continue;
		$site = $ARRAY_busquedas[$sitename];
		$html .= "<li>";
		$url = $site['url'];
		if($site['encode']=='utf8'){
			$url = str_replace('STRING_BUSQUEDA', urlencode(utf8_encode($string_busqueda)), $url);
		}else{
			$url = str_replace('STRING_BUSQUEDA', $string_busqueda, $url);
		}

		$html .= '<a href="'.$url.'" target="_blank" title="'.LABEL_Buscar.' '.$arrayTema[titTema].'  ('.$site[leyenda].')">';
		$html .= '<img src="'.T3_WEBPATH.'/images/'.$site[favicon].'" alt="'.LABEL_Buscar.' '.$arrayTema[titTema].'  ('.$site[leyenda].')"/>';
		$html .= '</a>';
		$html .= "</li>";
	}
	$html .= "</ul>";

	return $html;
};


#
# Expande una busqueda hacia arriba == busca los términos más generales de los términos especificos devueltos en una busqueda
#
function HTMLbusquedaExpandidaTG($acumula_indice,$acumula_temas,$string){

	global $DBCFG;

	$array_indice = explode("|", $acumula_indice);
	$array_temas = explode("|", $acumula_temas);

	$cantValores=array_count_values($array_indice);
	$array_indice=array_unique($array_indice);

	while (list($key, $val) = each($array_indice))
	{
		if(!in_array($val,$array_temas))
		{
			$temas_ids.=$val.',';
		}
	};


	//Si no hay términos más genericos que los resultados
	if(@$temas_ids)	{
		$sql=SQLlistaTema_id(substr($temas_ids,0,-1));

		$recordCount=SQLcount($sql);

		//Si hay resultados
		if($recordCount>0){
			$row_result.= '<p class="alert alert-info" role="alert"><strong>'.$recordCount.'</strong> '.LABEL_resultados_suplementarios.': <strong> "<em>'.stripslashes($string).'</em>"</strong></p>';
			$row_result.='<ul >';
			while($resulta_busca=$sql->FetchRow()){
				$ibusca=++$ibusca;
				$row_result.='<li>'.HTMLlinkTerm($resulta_busca).'</li>';
			}
			$row_result.='</ul>';
		};
	}

	return array("html"=>$row_result,"count"=>$recordCount);

};



#
# Expande una busqueda hacia terminos relacionados == busca los términos relacionados de los términos especificos devueltos en una busqueda
#
function HTMLbusquedaExpandidaTR($acumula_temas,$string){

	$temas_ids=str_replace("|",",", $acumula_temas);
	//Si no hay términos más genericos que los resultados
	if(@$temas_ids){

		$sql=SQLexpansionTR(substr($temas_ids,0,-1));
		$recordCount=SQLcount($sql);

		//Si hay resultados
		if($recordCount>0){
			$row_result.= '<p class="alert alert-info" role="alert"><strong>'.$recordCount.'</strong> '.LABEL_resultados_relacionados.': <strong> "<em>'.stripslashes($string).'</em>"</strong></p>';
			$row_result.='<ul >';

			while($resulta_busca=$sql->FetchRow()){
				$ibusca=++$ibusca;
				$row_result.='<li>'.HTMLlinkTerm($resulta_busca).'</li>';
			}
			$row_result.='</ul>';
		}
	}
	return array("html"=>$row_result,"count"=>$recordCount);
};


function HTMLverTE($tema_id,$i_profundidad,$i=""){

	GLOBAL $CFG;
	$sql=SQLverTerminosE($tema_id);
	$rows='<ul id="masTE'.$tema_id.'"  style="list-style:none; display: none">';
	//Contador de profundidad de TE desde la raíz
	$i_profundidad=($i_profundidad==0) ? 1 : $i_profundidad;
	$i_profundidad=++$i_profundidad;

	//Contador de profundidad de TE desde el TE base
	$i=++$i;
	while($array=$sql->FetchRow()){
		if($array["id_te"]){
			if($i<CFG_MAX_TREE_DEEP){
				$link_next='  <a href="javascript:expand(\''.$array[id_tema].'\')" title="'.LABEL_verDetalle.' '.$array["tema"].' ('.TE_termino.')" ><span id ="expandTE'.$array["id_tema"].'">&#x25ba;</span><span id ="contraeTE'.$array["id_tema"].'" style="display: none">&#x25bc;</span></a> ';
				$link_next.=HTMLverTE($array["id_tema"],$i_profundidad,$i);
			}		else {
				$link_next='&nbsp; <a title="'.LABEL_verDetalle.TE_termino.' '.$array["tema"].'" href="'.URL_BASE.'index.php?tema='.$array["id_tema"].'">&#x25ba;</a>';
				//$link_next=JHTMLverTE($tema_id);
			}
		}else{
			$link_next='';
		};

		$css_class_MT=($array["isMetaTerm"]==1) ? ' class="metaTerm" ' : '';

		$label_MT=($array["isMetaTerm"]==1) ? NOTE_isMetaTerm : '';

		$rows.='<li class="listTE"><abbr class="thesacronym" title="'.TE_termino.'" lang="'.LANG.'">'.TE_acronimo.$i_profundidad.'</abbr> ' ;
		$rows.=HTMLshowCode($array);
		$rows.=' <a '.$css_class_MT.' title="'.LABEL_verDetalle.' '.$array["tema"].' ('.TE_termino.') '.$label_MT.'"  href="'.URL_BASE.'index.php?tema='.$array["id_tema"].'&amp;/'.string2url($array["tema"]).'">'.$array["tema"].'</a>'.$link_next.'</li>';
	};
	$rows.='</ul>';

	return $rows;
}


function JHTMLverTE($tema_id){

	GLOBAL $CFG;
	return '<div id="treeTerm" data-url="suggest.php?node='.$tema_id.'"></div>';
}


function HTMLlistaTerminosEstado($estado_id,$limite="")
{

	//Estados posibles y aceptados
	$arrayEstados_id=array(12,14);

	//Descripcion de estados
	$arrayEstados=array("12"=>LABEL_Candidatos,"13"=>LABEL_Aceptados,"14"=>LABEL_Rechazados);

	if(in_array($estado_id,$arrayEstados_id)){

		$sql=SQLterminosEstado($estado_id,$limite);

		$rows.='<div><h3>'.ucfirst($arrayEstados[$estado_id]).' ('.SQLcount($sql).') </h3>';

		if(SQLcount($sql)>0){
			$rows.='<div class="table-responsive"> ';
			$rows.='<table id="termaudit" class="table table-striped table-bordered table-condensed table-hover">
			<thead>
			<tr>
				<th>'.ucfirst(LABEL_Termino).'</th>
				<th>'.ucfirst(LABEL_Fecha).'</th>
			</tr>
			</thead>
			<tbody>';

			while ($array = $sql->FetchRow()){

				$styleTerm='estado_termino'.$array[estado_id];

				$css_class_MT=($array["isMetaTerm"]==1) ? ' class="metaTerm" ' : '';
				$rows.= '<tr>';
				$rows.=  '     	<td>'.HTMLlinkTerm($array,array("style"=>$styleTerm,"modal"=>1)).'</td>';
				$rows.=  '      <td>'.$array["cuando"].'</td>';
				$rows.=  ' </tr>';

			};
			$rows.='        </tbody>		</table>';
			$rows.='        </div>';
	}
}//if in_array

	$rows.='</div>';

	return $rows;
};


function HTMLlistaTerminosFecha($limite="")
{

	//Descripcion de estados
	$arrayEstados=array("12"=>LABEL_Candidatos,"13"=>LABEL_Aceptados,"14"=>LABEL_Rechazados);


		$sql=SQLlastTerms($limite);

		$rows.='<div><h3>'.ucfirst(LABEL_newsTerm).'</h3>';

		if(SQLcount($sql)>0){
			$rows.='<div class="table-responsive"> ';
			$rows.='<table id="termaudit" class="table table-striped table-bordered table-condensed table-hover">
			<thead>
			<tr>
				<th>'.ucfirst(LABEL_Termino).'</th>
				<th>'.ucfirst(LABEL_lastChangeDate).'</th>
			</tr>
			</thead>
			<tbody>';

			while ($array = $sql->FetchRow()){
				$css_class_MT=($array["isMetaTerm"]==1) ? ' class="metaTerm" ' : '';
				$styleTerm='estado_termino'.$array[estado_id];

				$fecha=(@$array["cuando_final"]) ? $array["cuando_final"] : $array["cuando"];
				$rows.= '<tr>';
				$rows.=  '     	<td>'.HTMLlinkTerm($array,array("style"=>$styleTerm,"modal"=>1)).'</td>';
				$rows.=  '      <td>'.$fecha.'</td>';
				$rows.=  ' </tr>';

			};
			$rows.='        </tbody>		</table>';
			$rows.='        </div>';
	}

	$rows.='</div>';

	return $rows;
};



function HTMLsugerirTermino($texto,$acumula_temas="0"){

	$sqlSimilar=SQLsimiliar($texto,$acumula_temas);

	if(SQLcount($sqlSimilar)>0)
	{
		while($arraySimilar=$sqlSimilar->FetchRow()){
			$listaCandidatos.= $arraySimilar[tema].'|';
		}

		$listaCandidatos=explode("|",$listaCandidatos);
		$similar = new Qi_Util_Similar($listaCandidatos, $texto);
		$sugerencia= $similar->sugestao();

		$evalSimilar=evalSimiliarResults($texto, $sugerencia);

		if ($sugerencia && ($evalSimilar))
		{
			$rows.='<h4>'.ucfirst(LABEL_TERMINO_SUGERIDO).' <em><strong><a href="'.URL_BASE.'index.php?'.FORM_LABEL_buscar.'='.$sugerencia.'&amp;sgs=off" title="'.LABEL_verDetalle.$sugerencia.'">'.$sugerencia.'</a></strong></em> </h4>';
		}
	}
	return $rows;
}


/*
Display top terms
*/
function HTMLtopTerms($letra=""){

	GLOBAL $CFG;

	$_TOP_TERMS_BROWSER=(in_array($CFG["_TOP_TERMS_BROWSER"], array(1,0))) ? $CFG["_TOP_TERMS_BROWSER"] : 0;

	$rows.='<div class="clearer-top"></div>';

	//$_TOP_TERMS_BROWSER=1;
	if($_TOP_TERMS_BROWSER==1){
		//Top terms
		$sql=SQLverTopTerm();

		while ($array = $sql->FetchRow()){
			$rows.='<h2 class="TThtml">';
				$rows.=HTMLshowCode($array);
				$rows.=HTMLlinkTerm($array);
				$rows.='  <a href="javascript:expand(\''.$array["id"].'\')" title="'.LABEL_verDetalle.' '.$array["tema"].' ('.TE_termino.')" ><span id ="expandTE'.$array["id"].'">&#x25ba;</span><span id ="contraeTE'.$array["id"].'" style="display: none">&#x25bc;</span></a> ';
				$rows.='</h2>' ;
				$rows.=HTMLverTE($array["id"],1,0);
			};

	}else{
		$rows.='<div id="treeTerm" data-url="suggest.php?node=TT"></div>';
	}

	return $rows;
}


function HTMLlistaAlfabeticaUnica($letra=""){

	$menuNoAlfabetico='';
	$menuAlfabetico='';

	$sqlMenuAlfabetico=SQLlistaABC($letra);
	if(SQLcount($sqlMenuAlfabetico)>0){
		$rows='<ul class="pagination pagination-sm">';
		while ($datosAlfabetico = $sqlMenuAlfabetico->FetchRow())	{
			$datosAlfabetico[0]=isValidLetter($datosAlfabetico[0]);

			//is a valid letter
			if(strlen($datosAlfabetico[0])>0)			{
				$class = ($datosAlfabetico[1]==0)  ? '' : 'active';

				if(!ctype_digit($datosAlfabetico[0]))				{
					$menuAlfabetico.='<li class="'.$class.'">';
					$menuAlfabetico.='    <a title="'.LABEL_verTerminosLetra.' '.$datosAlfabetico[0].'" href="'.URL_BASE.'index.php?letra='.$datosAlfabetico[0].'">'.$datosAlfabetico[0].'</a>';
					$menuAlfabetico.='</li>';
				}				else				{
					$menuNoAlfabetico='<li class="'.$class.'">';
					$menuNoAlfabetico.='    <a title="'.LABEL_verTerminosLetra.' '.$datosAlfabetico[0].'" href="'.URL_BASE.'index.php?letra='.$datosAlfabetico[0].'">0-9</a>';
					$menuNoAlfabetico.='</li>';
				}
			}
		};//fin del while
	}

	$menuAlfabetico='<div class="text-center"><ul class="pagination pagination-sm">'.$menuNoAlfabetico.$menuAlfabetico.'</ul></div>';
	return $menuAlfabetico;
};


/*
All terms form one char
*/
function HTMLterminosLetra($letra)
{


	$cantLetra=numTerms2Letter($letra);

	$letra_label= (!ctype_digit($letra)) ?  $letra : '0-9';

	$terminosLetra.='<ol class="breadcrumb">';
	$terminosLetra.='<li><a title="'.MENU_Inicio.'" href="'.URL_BASE.'index.php">'.ucfirst(MENU_Inicio).'</a></li>';
	$terminosLetra.='<li class="active"><em>'.$letra_label.'</em>: <strong>'.$cantLetra.' </strong>'.LABEL_Terminos.'</li>';
	$terminosLetra.='</ol>';


	$paginado_letras='';

	$pag= secure_data($_GET["p"]);

	if($cantLetra>0)
	{

		if($cantLetra>CFG_NUM_SHOW_TERMSxSTATUS)
		{


			$paginado_letras=paginate_links( array(
				'type' => 'list',
				'show_all' => (($cantLetra/CFG_NUM_SHOW_TERMSxSTATUS)<15) ? true : false,
				'base' => 'index.php?letra='.$letra.'%_%',
				'format' => '&amp;p=%#%',
				'current' => max( 1, $pag),
				'total' => $cantLetra/CFG_NUM_SHOW_TERMSxSTATUS
				)
			);
		};

		$limit=CFG_NUM_SHOW_TERMSxSTATUS;


		$min= ($pag-1)*$limit;

		$sqlDatosLetra=SQLmenuABCpages($letra,array("min"=>$min,"limit"=>$limit));

		$start_ol=($min>0) ? $min+1 :1;

		$terminosLetra.='<div id="listaLetras"><ol start="'.$start_ol.'">';

		while ($datosLetra= $sqlDatosLetra->FetchRow()){

			//Si no es un término preferido
			if($datosLetra[termino_preferido]){
				switch($datosLetra[t_relacion]){
					//UF
					case '4':
					$leyendaConector=USE_termino;
					break;
					//Tipo relacion término equivalente parcialmente
					case '5':
					$leyendaConector='<abbr title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr>';
					break;
					//Tipo relacion término equivalente
					case '6':
					$leyendaConector='<abbr title="'.LABEL_termino_equivalente.'" lang="'.LANG.'">'.EQ_acronimo.'</abbr>';
					break;
					//Tipo relacion término no equivalente
					case '7':
					$leyendaConector='<abbr title="'.LABEL_termino_no_equivalente.'" lang="'.LANG.'">'.NEQ_acronimo.'</abbr>';
					break;
					//Tipo relacion término equivalente inexacta
					case '8':
					$leyendaConector='<abbr title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr>';
					break;
				}

				$terminosLetra.='<li><em><a title="'.LABEL_verDetalle.xmlentities($datosLetra[tema]).'" href="'.URL_BASE.'index.php?tema='.$datosLetra[tema_id].'&amp;/'.string2url($datosLetra[tema]).'">'.$datosLetra[tema].'</a></em> '.$leyendaConector.' <a title="'.LABEL_verDetalle.$datosLetra[tema].'" href="'.URL_BASE.'index.php?tema='.$datosLetra[id_definitivo].'&amp;/'.($datosLetra[termino_preferido]).'">'.$datosLetra[termino_preferido].'</a></li>'."\r\n" ;
			}
			else
			{
				$styleClassLink= ($datosLetra[estado_id]!=='13') ? 'estado_termino'.$datosLetra[estado_id] : '';
				$styleClassLinkMetaTerm= ($datosLetra["isMetaTerm"]=='1') ? 'metaTerm' : '';

				$terminosLetra.='<li><a class="'.$styleClassLink.' '.$styleClassLinkMetaTerm.'"  title="'.LABEL_verDetalle.xmlentities($datosLetra[tema]).'" href="'.URL_BASE.'index.php?tema='.$datosLetra[id_definitivo].'&amp;/'.string2url($datosLetra[tema]).'">'.xmlentities($datosLetra[tema]).'</a></li>'."\r\n" ;
			}
		};
		$terminosLetra.='   </ol>';
		$terminosLetra.='</div>';
	};


	$terminosLetra.='<div class="row">'.$paginado_letras.'</div>';

	return $terminosLetra;
}



#
# Armado de resultados de búsqueda avanzada
#
function HTMLadvancedSearchResult($array){

	//Ctrol lenght string
	$array["xstring"]=trim(XSSprevent($array["xstring"]));

	if(strlen(trim($array[xstring]))>=CFG_MIN_SEARCH_SIZE)
	{
		$sql= SQLadvancedSearch($array);


		$sql_cant=SQLcount($sql);

		$classMensaje= ($sql_cant>0) ? 'info' : 'danger';

		$resumeResult = '<p id="adsearch" class="alert alert-'.$classMensaje.'" role="alert"><strong>'.$sql_cant.'</strong> '.MSG_ResultBusca.' <strong> "<em>'.stripslashes($array[xstring]).'</em>"</strong></p>';
	}
	else
	{
		$sql_cant='0';
		$resumeResult = '<p id="adsearch" class="error">'.sprintf(MSG_minCharSerarch,stripslashes($array[xstring]),strlen($array[xstring]),CFG_MIN_SEARCH_SIZE-1).'</p>';
	}

	$body.=$resumeResult;

	if($sql_cant>0)
	{
		$row_result.='<div id="listaBusca"><ul class="list-unstyled" >';

		while($resulta_busca=$sql->FetchRow()){

			$ibusca=++$ibusca;
			$css_class_MT=($resulta_busca["isMetaTerm"]==1) ? ' class="metaTerm" ' : '';

			//Si no es un término preferido
			if($resulta_busca[uf_tema_id])
			{
				switch($resulta_busca[t_relacion])
				{
					case '4':					//UF
					$leyendaConector=USE_termino;
					break;

					case '5'://Tipo relacion término equivalente parcialmente
					$leyendaConector='<abbr title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr>';
					break;

					case '6'://Tipo relacion término equivalente
					$leyendaConector='<abbr title="'.LABEL_termino_equivalente.'" lang="'.LANG.'">'.EQ_acronimo.'</abbr>';
					break;

					case '7'://Tipo relacion término no equivalente
					$leyendaConector='<abbr title="'.LABEL_termino_no_equivalente.'" lang="'.LANG.'">'.NEQ_acronimo.'</abbr>';
					break;

					case '8'://Tipo relacion término equivalente inexacta
					$leyendaConector='<abbr title="'.LABEL_termino_parcial_equivalente.'" lang="'.LANG.'">'.EQP_acronimo.'</abbr>';
					break;
				}

				$row_result.='<li><em><a title="'.LABEL_verDetalle.$resulta_busca[tema].'" href="'.URL_BASE.'index.php?tema='.$resulta_busca[uf_tema_id].'&amp;/'.string2url($resulta_busca[uf_tema]).'">'.$resulta_busca[uf_tema].'</a></em> '.$leyendaConector.' <a title="'.LABEL_verDetalle.$resulta_busca[tema].'" href="'.URL_BASE.'index.php?tema='.$resulta_busca[tema_id].'">'.$resulta_busca[tema].'</a> </li>'."\r\n" ;
			}
			else // es un término preferido
			{
				$row_result.='<li>'.HTMLlinkTerm($resulta_busca).'</li>' ;
			}

		};//fin del while
		$row_result.='</ul>';
		$row_result.='</div>';


	};// fin de if result


	return $body.$row_result;
};


/*
Show terms from target vocabularies
*/
function HTMLtargetTerms($tema_id)
{
	$sql=SQLtargetTerms($tema_id);

	if (SQLcount($sql)>0)	{
		$rows='<ul class="list-unstyled">';

		while ($array=$sql->FetchRow())		{
			if ($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"])			{
				$delLink= '<a type="button" class="btn btn-danger btn-xs" id="elimina_'.$array["tterm_id"].'" title="'.LABEL_borraRelacion.'"  href="'.URL_BASE.'index.php?tterm_id='.$array["tterm_id"].'&amp;tema='.$tema_id.'&amp;tvocab_id='.$array[tvocab_id].'&amp;taskrelations=delTgetTerm" onclick="return askData();"><span class="glyphicon  icon-remove"></span></a> ';
				$checkLink= '<a id="actua_'.$array["tterm_id"].'" title="'.LABEL_ShowTargetTermforUpdate.'"  class="btn btn-warning btn-xs" href="'.URL_BASE.'index.php?tterm_id='.$array["tterm_id"].'&amp;tema='.$tema_id.'&amp;tvocab_id='.$array[tvocab_id].'&amp;tterm_id='.$array["tterm_id"].'&amp;taskEdit=checkDateTermsTargetVocabulary">'.LABEL_ShowTargetTermforUpdate.'</a>';

				$ttermManageLink=' '.$delLink.' '.$checkLink.'  ';

			}

			$rows.='<li>'.$ttermManageLink.' '.FixEncoding(ucfirst($array["tvocab_label"])).' <a href="modal.php?tema='.$tema_id.'&tterm_id='.$array["tterm_id"].'"  class="modalTrigger" title="'.FixEncoding($array[tterm_string]).'">'.FixEncoding($array["tterm_string"]).'</a>';
			$rows.=(($_GET["taskEdit"]=='checkDateTermsTargetVocabulary') && ($_GET["tterm_id"]==$array["tterm_id"]) && ($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"])) ? HTMLcheckTargetTerm($array) : '';
			$rows.='</li>';

		}
		$rows.='</ul>';
	}

	return $rows;
}

/*
Show URIs associated to term
*/
function HTMLURI4term($tema_id)
{
	$sql=SQLURIxterm($tema_id);

	if (SQLcount($sql)>0)	{
		$rows='<ul class="list-unstyled">';

		while ($array=$sql->FetchRow())		{
			if ($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]){
				$delLink= '<a type="button" class="btn btn-danger btn-xs" id="elimina_'.$array["uri_id"].'" title="'.LABEL_borraRelacion.'"  href="'.URL_BASE.'index.php?uri_id='.$array[uri_id].'&amp;tema='.$tema_id.'&amp;taskrelations=delURIterm" onclick="return askData();"><span class="glyphicon  icon-remove"></span></a> ';
				}


			$rows.='<li>'.$delLink.' '.ucfirst($array["uri_value"]).': ';

			$url_parts=parse_url($array["uri"]);
			if(in_array($url_parts['scheme'],array('http','https'))){
				$rows.=' <a href="'.$array["uri"].'" target="_blank" title="'.ucfirst($array[uri_value]).'">'.$array["uri"].'</a>';
			}else{
				$rows.=' '.$array["uri"];
			}
			$rows.='</li>';
		}
	$rows.='</ul>';
	}

	return $rows;
}


/*
check changes in one foreing term
*/
function HTMLcheckTargetTerm($array)
{

	$dataSimpleChkUpdateTterm=dataSimpleChkUpdateTterm("tematres",$array["tterm_uri"]);

	if($dataSimpleChkUpdateTterm->result->term->term_id)
	{
		$tterm_string=(string) $dataSimpleChkUpdateTterm->result->term->string;
		$tterm_id= (int) $dataSimpleChkUpdateTterm->result->term->term_id;
		$tterm_date=$dataSimpleChkUpdateTterm->result->term->date_mod;
	}

	$last_term_update=($array["cuando_last"]) ? $array["cuando_last"] : $array["cuando"];

	/*
	El término no existe más en el vocabulario de destino
	*/
	if($tterm_id<1)
	{
		$rows.= '<ul class="errorNoImage list-unstyled">';
		$rows.= '<li><strong>'.ucfirst(LABEL_notFound).'</strong></li>';
		$rows.= '<li><a href="'.URL_BASE.'index.php?tvocab_id='.$array["tvocab_id"].'&amp;tterm_id='.$array["tterm_id"].'&amp;tema='.$array["tema_id"].'&amp;taskrelations=delTgetTerm" class="eliminar" title="'.ucfirst(LABEL_borraRelacion).'">'.ucfirst(LABEL_borraRelacion).'</a></li>';
		$rows.= '</ul>';
	}
	/*
	hay actualizacion del término
	*/
	elseif ($tterm_date > $last_term_update)
	{
		$ARRAYupdateTterm["$array[tterm_uri]"]["string"]=FixEncoding($tterm_string);
		$ARRAYupdateTterm["$array[tterm_uri]"]["date_mod"]=$tterm_date;

		$rows.= '<ul class="warningNoImage list-unstyled">';
		$rows.= '<li><strong>'.$tterm_string.'</strong></li>';
		$rows.= '<li>'.$tterm_date.'</li>';
		$rows.= '<li><a href="'.URL_BASE.'index.php?tvocab_id='.$array["tvocab_id"].'&amp;tterm_id='.$array["tterm_id"].'&amp;tgetTerm_id='.$array["tterm_id"].'&amp;tema='.$array["tema_id"].'&amp;taskrelations=updTgetTerm" class="btn btn-success btn-xs" title="'.ucfirst(LABEL_actualizar).'">'.ucfirst(LABEL_actualizar).'</a></li>';
		$rows.= '<li><a href="'.URL_BASE.'index.php?tvocab_id='.$array["tvocab_id"].'&amp;tterm_id='.$array["tterm_id"].'&amp;tema='.$array["tema_id"].'&amp;taskrelations=delTgetTerm" title="'.ucfirst(LABEL_borraRelacion).'" class="btn btn-danger btn-xs">'.ucfirst(LABEL_borraRelacion).'</a></li>';
		$rows.= '</ul>';
	}
	else{
		$rows='<span class="successNoImage">'.LABEL_termUpdated.'</span>';
	}

	return $rows;
}



/**
* Retrieve paginated link for archive post pages.
*
* Technically, the function can be used to create paginated link list for any
* area. The 'base' argument is used to reference the url, which will be used to
* create the paginated links. The 'format' argument is then used for replacing
* the page number. It is however, most likely and by default, to be used on the
* archive post pages.
*
* The 'type' argument controls format of the returned value. The default is
* 'plain', which is just a string with the links separated by a newline
* character. The other possible values are either 'array' or 'list'. The
* 'array' value will return an array of the paginated link list to offer full
* control of display. The 'list' value will place all of the paginated links in
* an unordered HTML list.
*
* The 'total' argument is the total amount of pages and is an integer. The
* 'current' argument is the current page number and is also an integer.
*
* An example of the 'base' argument is "http://example.com/all_posts.php%_%"
* and the '%_%' is required. The '%_%' will be replaced by the contents of in
* the 'format' argument. An example for the 'format' argument is "?page=%#%"
* and the '%#%' is also required. The '%#%' will be replaced with the page
* number.
*
* You can include the previous and next links in the list by setting the
* 'prev_next' argument to true, which it is by default. You can set the
* previous text, by using the 'prev_text' argument. You can set the next text
* by setting the 'next_text' argument.
*
* If the 'show_all' argument is set to true, then it will show all of the pages
* instead of a short list of the pages near the current page. By default, the
* 'show_all' is set to false and controlled by the 'end_size' and 'mid_size'
* arguments. The 'end_size' argument is how many numbers on either the start
* and the end list edges, by default is 1. The 'mid_size' argument is how many
* numbers to either side of current page, but not including current page.
*
* It is possible to add query vars to the link by using the 'add_args' argument
* and see {@link add_query_arg()} for more information.
*
* @since 2.1.0
*
* @param string|array $args Optional. Override defaults.
* @return array|string String of page links or array of page links.
* http://codex.wordpress.org/Function_Reference/paginate_links
*/
function paginate_links( $args = '' ) {
	$defaults = array(
		'base' => '%_%', // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
		'format' => '?letra=%#%', // ?page=%#% : %#% is replaced by the page number
		'total' => 1,
		'current' => 0,
		'show_all' => false,
		'prev_next' => true,
		'prev_text' => ('&laquo; '.ucfirst(LABEL_Prev)),
		'next_text' => (ucfirst(LABEL_Next).' &raquo;'),
		'end_size' => 1,
		'mid_size' => 2,
		'type' => 'plain',
		'add_args' => false, // array of query args to add
		'add_fragment' => ''
	);

	$args = t3_parse_args( $args, $defaults );
	extract($args, EXTR_SKIP);

	// Who knows what else people pass in $args
	$total = (int) $total;
	if ( $total < 1 )
	return ;
	$current  = (int) $current;
	$end_size = 0  < (int) $end_size ? (int) $end_size : 1; // Out of bounds?  Make it the default.
	$mid_size = 0 <= (int) $mid_size ? (int) $mid_size : 2;
	$r = '';
	$page_links = array();
	$n = 0;
	$dots = false;

	if ( $prev_next && $current && 1 < $current ) :
		$link = str_replace('%_%', 2 == $current ? '' : $format, $base);
		$link = str_replace('%#%', $current - 1, $link);
		$link .= $add_fragment;
		$page_links[] = '<a class="previous-off" href="'.$link.'" title="' . $prev_text . '">' . $prev_text . '</a>';
	endif;
	for ( $n = 1; $n <= $total+1; $n++ ) :
		$n_display = $n;
		if ( $n == $current ) :
			$page_links[] = "<span class='page-numbers active'>$n_display</span>";
			$dots = true;
			else :
				if ( $show_all || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) :
					$link = str_replace('%_%', 1 == $n ? '' : $format, $base);
					$link = str_replace('%#%', $n, $link);
					$link .= $add_fragment;
					$page_links[] = '<a class="page-numbers" href="'.$link.'" title="'.LABEL_PageNum.' '.$n_display.'">'.$n_display.'</a>';
					$dots = true;
					elseif ( $dots && !$show_all ) :
						$page_links[] = '<span class="page-numbers dots">&hellip;</span>';
						$dots = false;
					endif;
				endif;
			endfor;

			if ( $prev_next && $current && ( $current <= $total || -1 == $total ) ) :
				$link = str_replace('%_%', $format, $base);
				$link = str_replace('%#%', $current + 1, $link);
				$link .= $add_fragment;
				$page_links[] = '<a class="next-off" href="'.$link.'" title="' . $next_text . '">' . $next_text . '</a>';
			endif;
			switch ( $type ) :
				case 'array' :
				return $page_links;
				break;
				case 'list' :
				$r .= "<ul class='pagination pagination-sm'>\n\t<li>";
				$r .= join("</li>\n\t<li>", $page_links);
				$r .= "</li>\n</ul>\n";
				break;
				default :
				$r = join("\n", $page_links);
				break;
			endswitch;

			return $r;
		}

		/**
		* Retorna los datos, acorde al formato de autocompleter
		*/
		function getData4Autocompleter($searchq,$type=1)
		{
			$sql=($type==1) ? SQLstartWith($searchq) : SQLbuscaTerminosSimple($searchq,"15");

			$arrayResponse=array("query"=>$searchq,
			"suggestions"=>array(),
			"data"=>array());

			while($array=$sql->FetchRow())
			{
				array_push($arrayResponse["suggestions"], $array["tema"]);
				array_push($arrayResponse["data"], $array["tema_id"]);
			}

			return json_encode($arrayResponse);
		};

		/*
		Retorna los datos, acorde al formato de jtree
		*/
		function getData4jtree($term_id=0){

			GLOBAL $CFG;
			if(is_numeric($term_id)){
					# display narrower terms
					$sql=SQLverTerminosE($term_id);
				}elseif ($term_id=='TT') {
					# display top terms
					$sql=SQLverTopTerm();
				}else{
					return;
				}

			$arrayResponse=array();

			while($array=$sql->FetchRow())			{
				//there are NT?
				$load_on_demand=($array["id_te"]==0) ? false : true;

				//is top terms
				$load_on_demand=($term_id==0) ? true : $load_on_demand;

				if(($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]) && ($CFG["_USE_CODE"]=='1'))				{
					$pre_link=' '.$array["code"].' ';
					}
					elseif($CFG["_SHOW_CODE"]=='1')				{
						$pre_link=' '.$array["code"].' ';
					}
					else				{
						$pre_link='	';
					}

				$styleClassLink = ($term_id=='TT') ? 'TT' :'';

				$link='<span class="TT">'.$pre_link.HTMLlinkTerm($array,array("style"=>$styleClassLink)).'</span>';

				array_push($arrayResponse, array("label"=>"$link",
				"id"=>"$array[tema_id]",
				"load_on_demand"=>$load_on_demand));
			}
			return json_encode($arrayResponse);
		};


		#display metada about the_term
		function HTMLtermMetadata($arrayTerm,$arrayCantRelaciones){
			GLOBAL $CFG;
			$fecha_crea=do_fecha($arrayTerm["cuando"]);
			$fecha_estado=do_fecha($arrayTerm["cuando_estado"]);
			$body.='<dl class="dl-horizontal">';

			if(isset($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"])){
				$ARRAYuserData4term=ARRAYuserData4term($arrayTerm["tema_id"]);
				$termCreator=' ('.$ARRAYuserData4term["c_nombres"].' '.$ARRAYuserData4term["c_apellido"].')';
				$termMod=' ('.$ARRAYuserData4term["m_nombres"].' '.$ARRAYuserData4term["m_apellido"].')';
			}

			$body.='<dt>'.ucfirst(LABEL_Fecha).'</dt>';
			$body.='<dd>'.$fecha_crea["dia"].'-'.$fecha_crea["descMes"].'-'.$fecha_crea["ano"].' '.$termCreator.'</dd>';

			if($arrayTerm["cuando_final"]){
				$fecha_cambio=do_fecha($arrayTerm["cuando_final"]);
				$body.='<dt>'.ucfirst(LABEL_fecha_modificacion).'</dt>';
				$body.='<dd>'.$fecha_cambio["dia"].'-'.$fecha_cambio["descMes"].'-'.$fecha_cambio["ano"].'  '.$termMod.'</dd>';
			}

			$body.='<dt class="estado_termino'.$arrayTerm["estado_id"].'">'.ucfirst( arrayReplace(array("12","13","14"),array(LABEL_Candidato,LABEL_Aceptado,LABEL_Rechazado),$arrayTerm["estado_id"])).'</dt>';
			$body.='<dd class="estado_termino'.$arrayTerm["estado_id"].'">'.$fecha_estado["dia"].'-'.$fecha_estado["descMes"].'-'.$fecha_estado["ano"].'</dd>';

			$body.='<dt>'.ucfirst(LABEL_totalTermsDescendants).'</dt>';
			$body.='<dd> '.cantChildTerms($arrayTerm["idTema"]).'</dd>';

			$body.='<dt>'.ucfirst(LABEL_narrowerTerms).'</dt>';
			$body.='<dd>'.(($arrayCantRelaciones["cantNT"]) ? $arrayCantRelaciones["cantNT"] : 0).'</dd>';

			$body.='<dt>'.ucfirst(LABEL_altTerms).'</dt>';
			$body.='<dd> '.(($arrayCantRelaciones["cantUF"]) ? $arrayCantRelaciones["cantUF"] : 0).'</dd>';

			$body.='<dt>'.ucfirst(LABEL_relatedTerms).'</dt>';
			$body.='<dd> '.(($arrayCantRelaciones["cantRT"]) ? $arrayCantRelaciones["cantRT"] : 0).'</dd>';

			$body.='<dt>'.ucfirst(LABEL_notes).' </dt>';
			$body.='<dd> '.count($arrayTerm["notas"]).'</dd>';

			//Is admin and have descendant terms
			if(($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"]==1) && ($arrayCantRelaciones["cantNT"]>0)){
				$body.='<dt>'.ucfirst(LABEL_export).' <span class="glyphicon icon-download-alt"></span></dt>';
				$body.='<dd>';
				$body.='<ul class="list-inline" id="enlaces_xml">';
				$body.='        <li><a target="_blank" title="'.MENU_ListaAbc.'"  href="'.URL_BASE.'xml.php?dis=termAlpha&amp;term_id='.$arrayTerm["tema_id"].'"><span class="glyphicon glyphicon-list"></span> '.ucfirst(MENU_ListaAbc).'</a></li>';
				$body.='        <li><a target="_blank" title="'.ucfirst(MENU_ListaSis).'"  href="'.URL_BASE.'xml.php?dis=termTree&amp;term_id='.$arrayTerm["tema_id"].'"><span class="glyphicon glyphicon-tree-conifer"></span> '.ucfirst(MENU_ListaSis).'</a></li>';
				$body.='</ul>';
				$body.='</dd>';
			}


			$body.='<dt>'.ucfirst(LABEL_metadatos).'</dt>';
			$body.='<dd>';

			$body.='<ul class="list-inline" id="enlaces_xml">';
			$body.='        <li><a class="btn btn-info btn-xs" target="_blank" title="'.LABEL_verEsquema.' BS8723-5"  href="'.URL_BASE.'xml.php?bs8723Tema='.$arrayTerm["tema_id"].'">BS8723-5</a></li>';
			$body.='        <li><a class="btn btn-info btn-xs" target="_blank" title="'.LABEL_verEsquema.' Dublin Core"  href="'.URL_BASE.'xml.php?dcTema='.$arrayTerm["tema_id"].'">DC</a></li>';
			$body.='        <li><a class="btn btn-info btn-xs"  target="_blank" title="'.LABEL_verEsquema.' MADS"  href="'.URL_BASE.'xml.php?madsTema='.$arrayTerm["tema_id"].'">MADS</a></li>  ';
			$body.='        <li><a class="btn btn-info btn-xs"  target="_blank" title="'.LABEL_verEsquema.' Skos"  href="'.URL_BASE.'xml.php?skosTema='.$arrayTerm["tema_id"].'">SKOS-Core</a></li>';
			$body.='        <li><a class="btn btn-info btn-xs"  target="_blank" title="'.LABEL_verEsquema.' IMS Vocabulary Definition Exchange (VDEX)"  href="'.URL_BASE.'xml.php?vdexTema='.$arrayTerm["tema_id"].'">VDEX</a></li>';
			$body.='        <li><a class="btn btn-info btn-xs"  target="_blank" title="'.LABEL_verEsquema.' TopicMap"  href="'.URL_BASE.'xml.php?xtmTema='.$arrayTerm["tema_id"].'">XTM</a></li>';
			$body.='        <li><a class="btn btn-info btn-xs"  target="_blank" title="'.LABEL_verEsquema.' Zthes" href="'.URL_BASE.'xml.php?zthesTema='.$arrayTerm["tema_id"].'">Zthes</a></li>  ';
			$body.='        <li><a class="btn btn-info btn-xs"  target="_blank" title="'.LABEL_verEsquema.' JavaScript Object Notation" href="'.URL_BASE.'xml.php?jsonTema='.$arrayTerm["tema_id"].'">JSON</a></li>  ';
			$body.='        <li><a class="btn btn-info btn-xs"  target="_blank" title="'.LABEL_verEsquema.' JavaScript Object Notation for Linked Data" href="'.URL_BASE.'xml.php?jsonldTema='.$arrayTerm["tema_id"].'">JSON-LD</a></li>  ';
			$body.='</ul>';
			$body.='</dd>';

			$body.='<dt>'.ucfirst(LABEL_busqueda).'</dt>';
			$body.='<dd>';
			$body.=HTML_URLsearch($CFG[SEARCH_URL_SITES],$arrayTerm);
			$body.='</dd> ';
			$body.='</dl> ';
			# fin Div pie de datos

			return $body;

		}

function HTMLdeepStats(){

	$sql=SQLTermDeep();


	$rows.='<div class="table-responsive col-xs-6 col-md-4">
	  <table class="table  table-bordered table-condensed table-hover" summary="'.LABEL.'">
	  <thead><tr><tr><th>'.ucfirst(LABEL_deepLevel).'</th><th>'.ucfirst(LABEL_cantTerms).'</th></tr></thead>
	  <tbody>';
		while($array=$sql->FetchRow())			{
			$rows.='<tr><td class="centrado">'.ucfirst(LABEL_deepLevel).' '.$array["tdeep"].'</td><td class="centrado">'.$array["cant"].'</td>';
		};
		$rows.='</tbody>
		</table>
		</div>';

		return $rows;

}

function HTMLshowCode($arrayTerm){

	GLOBAL $CFG;
	//Editor de código
	if(($_SESSION[$_SESSION["CFGURL"]]["ssuser_id"]) && ($CFG["_USE_CODE"]=='1'))
	{
		$rows.='<div title="term code, click to edit" class="editable_textarea" id="code_tema'.$arrayTerm[tema_id].'">'.$arrayTerm["code"].'</div>';
	}
	elseif($CFG["_SHOW_CODE"]=='1')
	{
		$rows.=' '.$arrayTerm[code].' ';
	}

	return $rows;
}



function HTMLduplicatedTermsAlert($arrayDuplicatedTerms){

	$cantDuplicated=count($arrayDuplicatedTerms);

	$rows = '<div class="alert alert-danger" role="alert">';
			$rows.= '<h4>'.ucfirst(LABEL_duplicatedTerms).': '.$cantDuplicated.'</h4>';
			$rows.='<ul>';
			foreach ($arrayDuplicatedTerms as $term_id => $term) {
				$rows.= '<li>'.HTMLlinkTerm(array("tema_id"=>$term_id,"tema"=>$term)).'</li>';
			}
			$rows.='</ul>';
	$rows.= '<p>'.MSG_duplicatedTerms.'</p>';
	$rows.='</div>';

	return array("type_error"=>'duplicatedTerms',
				 "html"=>$rows)	;
}


function HTMLlinkTerm($arrayTerm,$arg=array()){

	$class=(@$arg["style"]) ? $arg["style"] : '';

	$class.=($arrayTerm["isMetaTerm"]==1) ? ' metaTerm' : '';

	$url_parts=parse_url($_SESSION["CFGURL"]);


	//if is modal link
	if($arg["modal"]==1){

		return '<a href="'.$url_parts['scheme'] . '://' . $url_parts['host'] . ':' . $url_parts['port'] . $url_parts['path'].'modal.php?tema='.$arrayTerm["tema_id"].'" class="modalTrigger '.$class.'">'.$arrayTerm["tema"].'</a>';
	}

	$urlTerm=$url_parts['scheme'] . '://' . $url_parts['host'] . ':' . $url_parts['port'] . $url_parts['path'].'index.php?tema='.$arrayTerm["tema_id"].'&amp;/'.string2url($arrayTerm["tema"]);


	return '<a class="'.$class.'" href="'.$urlTerm.'" title="'.LABEL_verDetalle.$arrayTerm["tema"].'" lang="'.$arrayTerm["lang"].'">'.$arrayTerm["tema"].'</a>';
}



function makeGlossary($notesType=array("NA"),$params=array()){

//default params
	$format=(in_array($params["format"],array("json"))) ? $params["format"] : 'json';
	$altTerms=(in_array($params["altTerms"],array(1,0))) ? $params["altTerms"] : 0;

	$arrayTerm=array(); // term array
	$arrayNoteType=array(); // available notes type
	$myArrayNotesType=array(); // notes type for use in glossary


	$sqlCantNotas=SQLcantNotas();
	while ($arrayCantNotas=$sqlCantNotas->FetchRow()){
		array_push($arrayNoteType,$arrayCantNotas["tipo_nota"]);
	};

	//eval if there are any of selected note type
	foreach ($notesType as $noteType) {
		if(in_array($noteType, $arrayNoteType))
		array_push($myArrayNotesType,$noteType);
	}

	//ask for prefered terms
	$sql=SQLreportTerminosPreferidos();
	while($array=$sql->FetchRow()){
		$description=''; //empty term description
		if(count($myArrayNotesType)>0){
			$sqlNotas=SQLdatosTerminoNotas($array["id"],$myArrayNotesType);
			while($arrayNote=$sqlNotas->FetchRow()){
				$description.=$arrayNote["nota"].' ';
				}
			}
			//only if there are note
			if(strlen($description)>0){

				$arrayTerm[]=array("term"=>$array["tema"],
													"description"=>html2txt($description));


				//include UF terms
				if($altTerms==1){
				$sqlAltTerms=SQLverTerminoRelaciones($array["id"]);

				while($arrayAltTerms=$sqlAltTerms->FetchRow()){
					if($arrayAltTerms[t_relacion]=='4'){
						$arrayTerm[]=array("term"=>$arrayAltTerms["tema"],
															"description"=>html2txt($description));
						}
					}
				}//end if uf
			}//en if description
	}
	return json_encode($arrayTerm);
}



function HTMLheader($metadata){

 $rows='   <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="'.T3_WEBPATH.'bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="'.T3_WEBPATH.'bootstrap/submenu/css/bootstrap-submenu.min.css" rel="stylesheet">
    <link href="//netdna.bootstrapcdn.com/font-awesome/3.0.2/css/font-awesome.css" rel="stylesheet" />
    <link href="'.T3_WEBPATH.'css/t3style.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->';
    $rows.=$metadata["metadata"];
 $rows.=' <link type="image/x-icon" href="'.T3_WEBPATH.'images/tematres.ico" rel="icon" />
  <link type="image/x-icon" href="'.T3_WEBPATH.'images/tematres.ico" rel="shortcut icon" />';
	return $rows;
}



function HTMLnavHeader(){

GLOBAL $CFG;

if(!isset($_SESSION[$_SESSION["CFGURL"]]["HTMLextraHeader"])) $_SESSION[$_SESSION["CFGURL"]]["HTMLextraHeader"]=HTMLextraDataHeader($CFG);

// here img
$rows.='<div class="container">
  <div class="header">
      <h1><a href="'.URL_BASE.'index.php" title="'.$_SESSION[CFGTitulo].': '.MENU_ListaSis.'">'.$_SESSION[CFGTitulo].'</a></h1>
      '.$_SESSION[$_SESSION["CFGURL"]]["HTMLextraHeader"].'
 </div>
</div>';


$rows.='<nav class="navbar navbar-inverse" role="navigation">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapsible">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" title="'.MENU_Inicio.' '.$_SESSION["CFGTitulo"].'" href="'.URL_BASE.'index.php">'.MENU_Inicio.'</a>
    </div>
    <div class="navbar-collapse collapse" id="navbar-collapsible">
      <ul class="nav navbar-nav navbar-right">
        <li><a title="'.LABEL_busqueda.'" href="'.URL_BASE.'index.php?xsearch=1">'.ucfirst(LABEL_BusquedaAvanzada).'</a></li>

        <li><a title="'.MENU_Sobre.'" href="'.URL_BASE.'sobre.php">'.MENU_Sobre.'</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-left">';

				//hay sesion de usuario
        if($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"]){
  			$rows.=HTMLmainMenu();
        }else{//no hay session de usuario

 			$rows.='<li><a href="login.php" title="'.MENU_MiCuenta.'">'.MENU_MiCuenta.'</a></li>';
        };

  $rows.='</ul>
      <form method="get" id="simple-search" name="simple-search" action="'.URL_BASE.'index.php" class="navbar-form">
        <div class="form-group" style="display:inline;">
          <div class="fill col2">
            <input class="form-control" id="query" name="'.FORM_LABEL_buscar.'"  type="search">
            <input class="btn btn-default" type="submit" value="'.LABEL_Buscar.'" />
          </div>
        </div>
      </form>
    </div>

  </div>
</nav>';

return $rows;
}



function HTMLjsInclude(){

  #	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
 $rows='<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
		 <!-- Include all compiled plugins (below), or include individual files as needed -->
		 <script src="'.T3_WEBPATH.'bootstrap/js/bootstrap.min.js"></script>
		 <script type="text/javascript" src="'.T3_WEBPATH.'jq/jquery.autocomplete.js"></script>
		 <script type="text/javascript" src="'.T3_WEBPATH.'jq/jquery.mockjax.js"></script>
		 <script type="text/javascript" src="'.T3_WEBPATH.'jq/tree.jquery.js"></script>

		 <link rel="stylesheet" type="text/css" href="'.T3_WEBPATH.'css/jquery.autocomplete.css" />
		 <link rel="stylesheet" type="text/css" href="'.T3_WEBPATH.'css/jqtree.css" />
		 <script type="text/javascript" src="'.T3_WEBPATH.'bootstrap/submenu/js/bootstrap-submenu.min.js"></script>
		 <script type="text/javascript" src="'.T3_WEBPATH.'bootstrap/bootstrap-tabcollapse.js"></script>
		 <link type="text/css" src="'.T3_WEBPATH.'bootstrap/forms/css/styles.css"/>';

if (isset($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"]) && ($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"]>0)){
//<!-- Load TinyMCE -->
 $rows.='<script type="text/javascript" src="'.T3_WEBPATH.'tiny_mce4/tinymce.min.js"></script>';
//<!-- /TinyMCE -->

 $rows.='	<link type="text/css" href="'.T3_WEBPATH.'jq/theme/ui.all.css" media="screen" rel="stylesheet" />
	<script type="text/javascript" src="'.T3_WEBPATH.'jq/jquery.jeditable.mini.js" charset="utf-8"></script>';
}

 $rows.='<script type="application/javascript" src="'.URL_BASE.'js.php" charset="utf-8"></script>
		<script type="text/javascript" src="'.T3_WEBPATH.'forms/jquery.validate.min.js"></script>
		<script type="text/javascript" src="'.T3_WEBPATH.'bootstrap/js/validator.min.js"></script>';

 if($_SESSION[$_SESSION["CFGURL"]]["lang"][2]!=='en')
 	$rows.='<script src="'.T3_WEBPATH.'forms/localization/messages_'.$_SESSION[$_SESSION["CFGURL"]]["lang"][2].'.js" type="text/javascript"></script>';

$rows.='<script type="text/javascript">
	  	$("#myTermTab").tabCollapse();
	  	$(".dropdown-submenu > a").submenupicker();

	  	$(".termDefinition").popover();
		$("#popoverOption").popover({ trigger: "hover"});
		$(".autoGloss").tooltip(options);
	  </script>';

//scritp to export form
if ((isset($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"])) &&
		($_SESSION[$_SESSION["CFGURL"]]["ssuser_nivel"]==1) &&
		($_GET["doAdmin"]=='export')){
$rows.='<script type=\'text/javascript\'>//<![CDATA[
					$(window).load(function(){
					$(\'#dis\').bind(\'change\', function(event) {
					    var x = $(\'#dis\').val();
					    if ((x == "txt") || (x == "spdf") || (x == "rpdf")) {
					        $(\'#txt_config\').show();
					    }else{
					        $(\'#txt_config\').hide();
					    }
					    if ((x == "txt") || (x == "rpdf")) {
					        $(\'#txt_config2\').show();
					    } else {
					        $(\'#txt_config2\').hide();
					    }
					    if (x == "rfile") {
					        $(\'#skos_config\').show();
					    } else{
					        $(\'#skos_config\').hide();
					    }
					});
					});//]]>
			</script>';
};

return $rows;
}

// specific note types for contextual term definition
function TXTtermDefinition($array,$noteType=array("DF","NA","SN")){

if(count($array["notas"])==0) return;

for($iNota=0; $iNota<(count($array["notas"])); ++$iNota){

	if($array["notas"][$iNota]["id"]){
		if((in_array($array["notas"][$iNota]["tipoNota"], $noteType)) && ($array["notas"][$iNota]["lang_nota"]==$_SESSION["CFGIdioma"])){
			$body.=html2txt($array["notas"][$iNota]["nota"]).' ';

			}
		};// fin del for
	};
return $body;
};


function HTMLdisplayRandomTerm($noteType="NA"){

$sql=SQLrandomTerms($noteType);


if(is_object($sql)){
	$arrayTerm=$sql->FetchRow();
	$ARRAYdatosTermino=ARRAYverDatosTermino($arrayTerm["term_id"]);
	for($iNota=0; $iNota<(count($ARRAYdatosTermino["notas"])); ++$iNota){

		if(($ARRAYdatosTermino["notas"][$iNota]["tipoNota"]==$noteType) && ($ARRAYdatosTermino["notas"][$iNota]["lang_nota"]==$_SESSION["CFGIdioma"])){
			$notes.=wiki2link($ARRAYdatosTermino["notas"][$iNota]["nota"]).' ';
		};// fin del for
	}

$rows='<div class="jumbotron">';
$rows.='  <h2>'.ucfirst($arrayTerm["tema"]).'</h2>';
$rows.='  <p style="text-align: justify" class="glossNote">'.$notes.'</p>';
$rows.='  <p><a class="btn btn-primary btn-lg" href="'.URL_BASE.'index.php?tema='.$arrayTerm["term_id"].'" role="button" title="'.ucfirst(LABEL_Detalle).' '.$arrayTerm["tema"].'">'.ucfirst(LABEL_Detalle).'</a></p>';
$rows.='</div>';
}

if($iNota>0) return $rows;
}


//redact extra link and image for header
function HTMLextraDataHeader($CFG){

//Check if there are data
if(strlen($CFG["HEADER_EXTRA"]["LINK_IMG"])>0){
	if(URL_exists($CFG["HEADER_EXTRA"]["LINK_IMG"])){
		$url_logo='<img src="'.$CFG["HEADER_EXTRA"]["LINK_IMG"].'" height="50px" alt="'.$CFG["HEADER_EXTRA"]["LINK_TITLE"].'">';
	}

	///make link
	if(strlen($CFG["HEADER_EXTRA"]["LINK_URL"])>0){
		$url_logo='<a href="'.$CFG["HEADER_EXTRA"]["LINK_URL"].'" title="'.$CFG["HEADER_EXTRA"]["LINK_TITLE"].'">'.$url_logo.'</a>';
	}
}
return $url_logo;
}





//select target vocabulary in trad module
function HTMLselectTargetVocabulary($tvocab_id=""){

	$sql=SQLinternalTargetVocabs();

	if(SQLcount($sql)=='0'){
			//No hay vocabularios de referencia, solo vocabulario principal
			$rows.=HTMLalertNoTargetVocabularyPivotModel($tvocab_id);
	} else {

		$rows.='<table class="table table-striped table-bordered table-condensed table-hover">
		<thead>
		<tr>
			<th>'.ucfirst(LABEL_vocabulario_referencia).'</th>
			<th>'.ucfirst(LABEL_cantTerms).'</th>
		</tr>
		</thead>
		<tbody>';
		while ($array = $sql->FetchRow()){
			$rows.= '<tr>';
			$rows.=  '     <td><a href="'.URL_BASE.'index.php?mod=trad&amp;tvocab_id='.$array["tvocab_id"].'" title="'.$array["titulo"].'">'.$array["titulo"].'</a> ('.strtoupper($array["idioma"]).')</td>';
			$rows.=  '      <td>'.$array["cant"].'</td>';
			$rows.=  '</tr>';
		};
		$rows.='        </tbody></table>';
		$rows.='</div>';

	}

	return $rows;
}


//alphabetic list of terms to browse table
function HTMLalphaListTerms4map($tvocab_id,$filterEQ,$char=""){

	$sqlMenuAlfabetico=SQLlistaABCPreferedTerms($char);


	if(SQLcount($sqlMenuAlfabetico)>0){

		$rows.='<ul class="pagination pagination-sm">';

		while ($datosAlfabetico = $sqlMenuAlfabetico->FetchRow())	{
			$datosAlfabetico[0]=isValidLetter($datosAlfabetico[0]);

			//is a valid letter
			if(strlen($datosAlfabetico[0])>0)			{
				$class = ($datosAlfabetico[1]==0)  ? '' : 'active';

				if(!ctype_digit($datosAlfabetico[0]))				{
					$menuAlfabetico.='<li class="'.$class.'">';
					$menuAlfabetico.='    <a title="'.LABEL_verTerminosLetra.' '.$datosAlfabetico[0].'" href="'.URL_BASE.'index.php?letra2trad='.$datosAlfabetico[0].'&tvocab_id='.$tvocab_id.'&mod=trad">'.$datosAlfabetico[0].'</a>';
					$menuAlfabetico.='</li>';
				}				else				{
					$menuNoAlfabetico='<li class="'.$class.'">';
					$menuNoAlfabetico.='    <a title="'.LABEL_verTerminosLetra.' '.$datosAlfabetico[0].'" href="'.URL_BASE.'index.php?letra2trad='.$datosAlfabetico[0].'&tvocab_id='.$tvocab_id.'&mod=trad">0-9</a>';
					$menuNoAlfabetico.='</li>';
				}
			}
		};//fin del while
	}

	$menuAlfabetico='<div class="text-center"><ul class="pagination pagination-sm">'.$menuNoAlfabetico.$menuAlfabetico.'</ul></div>';
	return $menuAlfabetico;
};


#
#  ARMADOR DE HTML CON DATOS DEL TERMINO
#
function HTMLsimpleTerm($arrayTerm){

	GLOBAL $CFG;
	$iNT=0;
	$iBT=0;
	$iRT=0;
	$iUF=0;

	//Terminos específicos
	$sqlNT=SQLverTerminosE($arrayTerm["arraydata"]["tema_id"]);

	while ($datosNT=$sqlNT->FetchRow()){
		$iNT=++$iNT;
			$css_class_MT=($datosNT["isMetaTerm"]==1) ? ' class="metaTerm" ' : '';

			$row_NT.=' <li '.$css_class_MT.' id="t'.$datosNT["id_tema"].'"><abbr class="thesacronym" id="r'.$datosNT["rel_id"].'" title="'.TE_termino.' '.$datosNT["rr_value"].'" lang="'.LANG.'">'.TE_acronimo.$datosNT["rr_code"].'</abbr> ';
			//ver  código
			$row_NT.=($CFG["_SHOW_CODE"]=='1') ? ' '.$datosNT["code"].' ' : '';

			$row_NT.=$datosNT["tema"].'</li>';
			};
	// Terminos TG, UF y TR
	$sqlTotalRelacionados=SQLverTerminoRelaciones($arrayTerm["arraydata"]["tema_id"]);

	while($datosTotalRelacionados= $sqlTotalRelacionados->FetchRow()){
		$classAcrnoyn='thesacronym';
		#Change to metaTerm attributes
		if(($datosTotalRelacionados["BT_isMetaTerm"]==1)){
			$css_class_MT= ' class="metaTerm" ';
			$label_MT=NOTE_isMetaTerm;
		}		else		{
			$css_class_MT= '';
			$label_MT='';
		}


		switch($datosTotalRelacionados["t_relacion"]){
			case '3':// TG
			$iBT=++$iBT;
			$row_BT.='<li><abbr class="'.$classAcrnoyn.'" id="edit_rel_id'.$datosTotalRelacionados["rel_id"].'" style="display: inline" title="'.TG_termino.' '.$datosTotalRelacionados[rr_value].'" lang="'.LANG.'">'.TG_acronimo.$datosTotalRelacionados["rr_code"].'</abbr>  '.$datosTotalRelacionados["tema"].'</li>';
			break;

			case '4':// UF
			//hide hidden equivalent terms
			$iUF=++$iUF;
				$row_UF.='<li><abbr class="'.$classAcrnoyn.'" id="edit_rel_id'.$datosTotalRelacionados["rel_id"].'" style="display: inline" title="'.UP_termino.' '.$datosTotalRelacionados["rr_value"].'" lang="'.LANG.'">'.UP_acronimo.$datosTotalRelacionados["rr_code"].'</abbr>   '.$datosTotalRelacionados["tema"].'</li>';
			break;

			case '2':// TR
			$iRT=++$iRT;
			$row_RT.='<li><abbr class="'.$classAcrnoyn.'" id="edit_rel_id'.$datosTotalRelacionados["rel_id"].'" style="display: inline" title="'.TR_termino.' '.$datosTotalRelacionados["rr_value"].'" lang="'.LANG.'">'.TR_acronimo.$datosTotalRelacionados["rr_code"].'</abbr>  '.$datosTotalRelacionados["tema"].'</li>';
			break;
		}
	};


	$BTrows=($iBT>0) ? '<h4>'.ucfirst(LABEL_broatherTerms).'</h4><ul class="list-unstyled" id="bt_data">'.$row_BT.'</ul>' :'';
	$NTrows=($iNT>0) ? '<h4>'.ucfirst(LABEL_narrowerTerms).'</h4><ul class="list-unstyled" id="nt_data">'.$row_NT.'</ul>':'';
	$RTrows=($iRT>0) ? '<h4>'.ucfirst(LABEL_relatedTerms).'</h4><ul class="list-unstyled" id="rt_data">'.$row_RT.'</ul>':'';
	$UFrows=($iUF>0) ? '<h4>'.ucfirst(LABEL_nonPreferedTerms).'</h4><ul class="list-unstyled" id="uf_data">'.$row_UF.'</ul>':'';

	return array("BTrows"=>$BTrows,
               "NTrows"=>$NTrows,
               "RTrows"=>$RTrows,
               "UFrows"=>$UFrows
             );
}



#
#  ARMADOR DE HTML CON DATOS DEL TERMINO
#
function HTMLsimpleForeignTerm($arrayTerm,$URL_ttermData){
  $NTrows='';
  $BTrows='';
  $RTrows='';
  $UFrows='';

  if(count($arrayTerm["ttermNT"])>0){
    foreach ($arrayTerm["ttermNT"] as $eachTerm) {
          			$row_NT.=' <li '.$css_class_MT.' id="t'.$eachTerm["term_id"].'"><abbr class="thesacronym"  title="'.TE_termino.' '.$eachTerm["rtype"].'">'.TE_acronimo.$eachTerm["rtype"].'</abbr> '.$eachTerm["string"].'</li>';
    }
  $NTrows='<h4>'.ucfirst(LABEL_narrowerTerms).'</h4><ul class="list-unstyled" id="nt_data">'.$row_NT.'</ul>';
  }

  if(count($arrayTerm["ttermBT"])>0){
    foreach ($arrayTerm["ttermBT"] as $eachTerm) {
          			$row_BT.=' <li '.$css_class_MT.' id="t'.$eachTerm[term_id].'"><abbr class="thesacronym"  title="'.TG_acronimo.' '.$eachTerm[rtype].'">'.TG_acronimo.$eachTerm[rtype].'</abbr> '.$eachTerm[string].'</li>';
    }
    $BTrows='<h4>'.ucfirst(LABEL_broatherTerms).'</h4><ul class="list-unstyled" id="bt_data">'.$row_BT.'</ul>';
  }

  if(count($arrayTerm["ttermRT"])>0){
    foreach ($arrayTerm["ttermRT"] as $eachTerm) {
          			$row_RT.=' <li '.$css_class_MT.' id="t'.$eachTerm[term_id].'"><abbr class="thesacronym"  title="'.TR_acronimo.' '.$eachTerm[rtype].'">'.TR_acronimo.$eachTerm[rtype].'</abbr> '.$eachTerm[string].'</li>';
    }
  $RTrows='<h4>'.ucfirst(LABEL_relatedTerms).'</h4><ul class="list-unstyled" id="rt_data">'.$row_RT.'</ul>';
  }

  if(count($arrayTerm["ttermUF"])>0){
    foreach ($arrayTerm["ttermUF"] as $eachTerm) {
          			$row_UF.=' <li  id="t'.$eachTerm[term_id].'"><abbr class="thesacronym"  title="'.UP_acronimo.' '.$eachTerm[rtype].'">'.UP_acronimo.$eachTerm[rtype].'</abbr> '.$eachTerm[string].'</li>';
    }
  $UFrows='<h4>'.ucfirst(LABEL_nonPreferedTerms).'</h4><ul class="list-unstyled" id="uf_data">'.$row_UF.'</ul>';
  }

return array("BTrows"=>$BTrows,"NTrows"=>$NTrows,"RTrows"=>$RTrows,"UFrows"=>$UFrows);
}
?>
