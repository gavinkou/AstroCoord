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
 * @property Angle    $alt  Altitude
 * @property Angle    $az   Azimuth
 * @property Distance $dist Distace
 */
class Horiz {

  protected $alt;
  protected $az;
  protected $dist;

  public function __construct(Angle $alt, Angle $az, Distance $dist = null) {
    $this->alt  = $alt;
    $this->az   = $az;
    $this->dist = $dist;
  }

  public function __get($name) {
    switch ($name) {
      case 'alt':
      case 'az':
      case 'dist':
        return $this->{$name};
    }
  }

  public function __toString() {
    $deFormat = "%+03.0f";
    $daFormat = "%+04.0f";
    $mFormat  = "%02.0f";
    $sFormat  = "%02.0f";

    $eD = sprintf($deFormat, $this->alt->d);
    $eM = sprintf($mFormat, abs($this->alt->m));
    $eS = sprintf($sFormat, abs($this->alt->s));

    $aD = sprintf($daFormat, abs($this->az->d));
    $aM = sprintf($mFormat, abs($this->az->m));
    $aS = sprintf($sFormat, abs($this->az->s));

    $emic = str_replace('0.', '.',
            round(abs(intval($this->alt->s) - $this->alt->s), 3));
    $emic = str_pad($emic, 4, '0', STR_PAD_RIGHT);
    $emic = round(abs(intval($this->alt->s) - $this->alt->s), 3) == 0 ?
            '.000' : $emic;

    $amic = str_replace('0.', '.',
            round(abs(intval($this->az->s) - $this->az->s), 3));
    $amic = str_pad($amic, 4, '0', STR_PAD_RIGHT);
    $amic = round(abs(intval($this->az->s) - $this->az->s), 3) == 0 ?
            '.000' : $amic;

    $dist='';//$dist = $this->dist ? " Dist {$this->dist}" : '';
    return "Alt {$eD}°{$eM}'{$eS}\"{$emic} Az {$aD}°{$aM}'{$aS}\"{$amic}{$dist}";
  }

}
