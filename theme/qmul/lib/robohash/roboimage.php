<?php

require_once('lib/Robohash.php');

class theme_qmul_robohash {

    protected $colours;
    protected $sets;
    protected $bgsets;

    public function __construct() {
        $this->colours = array('blue', 'brown', 'green', 'grey', 'orange', 'pink', 'purple', 'red', 'white', 'yellow');
        $this->sets = array('set1','set2', 'set3', 'set1','set2', 'set3', 'set1','set2', 'set3', 'set1');
        $this->bgsets = array('bg1', 'bg2', 'bg1', 'bg2', 'bg1', 'bg2', 'bg1', 'bg2', 'bg1', 'bg2');
    }

    public function generate($text) {

        $number = base_convert(md5($text), 16, 10);
        $number = substr($number, 4, 1);

        $colour = $this->colours[$number];
        $set = $this->sets[$number];
        $bgset = $this->bgsets[$number];

        $ext = 'png';

        $robohash = new Robohash(array(
                        'text'      => $text,
                        'bgset'     => $bgset,
                        'set'       => $set,
                        'size'      => 200,
                        'color'     => $colour,
                        'ext'       => $ext
        ));
        $base64 = $robohash->generate_image();


        return "data:image/png;base64,".$base64;
    }

}
