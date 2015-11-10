<?php

/*
 * Copyright (C) 2015 ashley
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Marando\AstroCoord;

use \Marando\Units\Angle;
use \Marando\Units\Distance;

/**
 * @property Angle    $lon  Ecliptic longitude
 * @property Angle    $lat  Ecliptic latitude
 * @property Distance $dist Observer to target distance
 */
class Eclip {

  protected $lon;
  protected $lat;
  protected $dist;

  public function __construct(Angle $lon, Angle $lat, Distance $dist = null) {
    $this->lon  = $lon;
    $this->lat  = $lat;
    $this->dist = $dist;
  }

  public function __get($name) {
    switch ($name) {
      case 'lon':
      case 'lat':
      case 'dist':
        return $this->{$name};
    }
  }



  public function __toString() {
    $deFormat = "%+03.0f";
    $daFormat = "%+04.0f";
    $mFormat  = "%02.0f";
    $sFormat  = "%02.0f";
    $eD       = sprintf($deFormat, $this->lon->d);
    $eM       = sprintf($mFormat, abs($this->lon->m));
    $eS       = sprintf($sFormat, abs($this->lon->s));
    $aD       = sprintf($daFormat, abs($this->lat->d));
    $aM       = sprintf($mFormat, abs($this->lat->m));
    $aS       = sprintf($sFormat, abs($this->lat->s));
    $emic     = str_replace('0.', '.',
            round(abs(intval($this->lon->s) - $this->lon->s), 3));
    $emic     = str_pad($emic, 4, '0', STR_PAD_RIGHT);
    $emic     = round(abs(intval($this->lon->s) - $this->lon->s), 3) == 0 ?
            '.000' : $emic;
    $amic     = str_replace('0.', '.',
            round(abs(intval($this->lat->s) - $this->lat->s), 3));
    $amic     = str_pad($amic, 4, '0', STR_PAD_RIGHT);
    $amic     = round(abs(intval($this->lat->s) - $this->lat->s), 3) == 0 ?
            '.000' : $amic;
    $dist     = ''; //$dist = $this->dist ? " Dist {$this->dist}" : '';
    return "Lon {$eD}°{$eM}'{$eS}\"{$emic} Lat {$aD}°{$aM}'{$aS}\"{$amic}{$dist}";
    //return "Az {$aD}°{$aM}'{$aS}\"{$amic} El {$eD}°{$eM}'{$eS}\"{$emic}{$dist}";
  }

}
