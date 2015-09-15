<?php


/**
 *
 */
class cad_Drawer extends core_MVC {
    
    function act_View()
    {
        $svg = new cad_SvgCanvas();
        
        $pen = array('stroke' => '#009900', 'stroke-width' => 0.3, 'fill' => 'yellow');

        $svg->startPath($pen);

        $svg->moveTo(10, 10, TRUE);
        $svg->lineTo(10, 287, TRUE);
        $svg->lineTo(300, 287, TRUE);
        $svg->lineTo(300, 10, TRUE);
        $svg->closePath();
        $svg->moveTo(20, 20, TRUE);
        $svg->lineTo(290, 20, TRUE);
        $svg->lineTo(290, 277, TRUE);
        $svg->lineTo(20, 277, TRUE);
        $svg->closePath();
    
        $svg->moveTo(100, 100, TRUE);

        $c = 4/3*(M_SQRT2-1);

        $svg->curveTo(100 * $c, 0, 100,  100 - $c * 100,  100,  100);

        //$svg->curveTo(100+ 100, 100 + 100 + 100 * $c, 100+ 100* $c, 100 + 100 + 100, 100, 100+ 100 + 100, TRUE);

 
        $res = $svg->render();
        
 
        return $res;
     }

     function act_Test()
    {
         $form = cls::get('core_Form');
         $form->FLD('shapeClass', 'class(interface=cad_ShapeIntf,allowEmpty,select=title)', 'caption=Фигура,silent', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit();")));
         $form->input(NULL, TRUE);
         $rec = $form->rec;
         if($rec->shapeClass) {
             $shape = cls::get($rec->shapeClass);
             $shape->addParams($form);
        }

        $form->input();

        if($form->isSubmitted()) {
            $params = (array) $rec;
            if($shape) {
                $canvas =  cls::get('cad_SvgCanvas');
                $shape->draw($canvas, $params);
            }
        }

        if($shape) {
            $form->title = tr('Чертаене на') . ' ' . tr($shape->title);
        } else {
            $form->title = tr('Изберете фигура');
        }
        
        $form->toolbar = cls::get('core_Toolbar');
        $form->toolbar->addSbBtn('Чертаене');

        $res = new ET("<div style='float:left;'>[#FORM#]</div><div style='float:left;'>[#SVG#]</div><div class='clearfix21'></div>");
        
        $res->replace($form->renderHtml(), 'FORM');
        
        if($canvas) {
            $res->replace($canvas->render(), 'SVG');
        }

        return $res;
    }
}