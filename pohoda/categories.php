<?php
/**
 * Import list of categories from Pohoda
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/functions.php');


$content = file_get_contents("php://input");
$file = "categories.xml";

if (!empty($content)) {
    $file = "categories" . date("_Y-m-d_H-i-s") . ".xml";
    file_put_contents($file, $content);
}

function importCategories($file, $blindMode)
{
    
    $xml = new XMLReader();
    $xml->open($file);
    
    $depth = array();
    
    $parentId = null;
    
    $parents = array();
    
    $depth = 0;
    
    $db = DbCore::getInstance();
    
    while ($xml->read()) {
        
        if ($xml->nodeType == XmlReader::ELEMENT && $xml->name == 'ctg:name') {
            $xml->read();
        
            $name = $xml->value;
        }
        
        if ($xml->nodeType == XmlReader::ELEMENT && $xml->name == 'ctg:description') {
            $xml->read();
        
            $description = $xml->value;
        }
        
        if ($xml->nodeType == XmlReader::ELEMENT && $xml->name == 'ctg:sequence') {
            $xml->read();
        
            $sequence = $xml->value;
        }
        
        
        
        if ($xml->nodeType == XmlReader::ELEMENT && $xml->name == 'ctg:id') {
            $xml->read();
            
            $id = (int) $xml->value;
            
            //echo "$id (" . @$parents[$depth] . ")<br>\n";
        }

        if ($xml->nodeType == XmlReader::ELEMENT && $xml->name == 'ctg:internetParams') {
            // hack - we insert category here
            $parent = 1;
            if (isset($parents[$depth])) {
                $parent = $parents[$depth];
            }

            if ($blindMode) {
                try {
                    // hack - we can not save category unless it already exists
                    $db->insert('category', array('id_category' => $id, 'id_parent' => $parent, 'date_add' => date('Y-m-d H:i:s')));
                } catch (Exception $e) {

                }
            } else {
                try {
                    $category = new Category();
                    $category->id = $id;
                    $category->id_category = $id;
                    $category->name = $name;
                    $category->link_rewrite = createUrlSlug($name);
                    $category->description = $description;
                    $category->position = $sequence;
                    $category->id_parent = $parent;
                    $category->doNotRegenerateNTree = true;
                    $category->save();
                } catch (Exception $e) {
                    echo $e->getMessage() . " $id $parent\n";
                }
            }
        }

        if ($xml->nodeType == XmlReader::ELEMENT && $xml->name == 'ctg:subCategories') {
            $depth++;
            
            if (!isset($parents[$depth])) {
                $parents[$depth] = $id;
            } 
        }
        
        if ($xml->nodeType == XmlReader::END_ELEMENT && $xml->name == 'ctg:subCategories') {
            $depth--;
        }
    }
    
    $xml->close();
}

importCategories($file, true);
importCategories($file, false);

Category::regenerateEntireNtree();

// Send feed
header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/response.xsd" version="2.0" id="00000001" state="ok" application="Prestashop" note="Prestashop import">
</rsp:responsePack>