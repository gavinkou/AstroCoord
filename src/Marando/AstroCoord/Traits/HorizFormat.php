<?php

/*
 * Copyright (C) 2015 Ashley Marando
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

namespace Marando\AstroCoord\Traits;

trait HorizFormat {

  /**
   * String format for the instance
   * @var string
   */
  protected $format;

  public function format($format) {
    // Store current format
    $this->format = $format;

    if (strstr($format, 'Hd')) {
      $repl   = strstr($format, '+Hd') ? '+Hd' : 'Hd';
      $fmt    = strstr($format, '+Hd') ? '%+03d' : '%02d';
      $str    = sprintf($fmt, $this->alt->d);
      $format = str_replace($repl, $str, $format);
    }

    if (strstr($format, 'Hm')) {
      $str    = sprintf('%02d', abs($this->alt->m));
      $format = str_replace('Hm', $str, $format);
    }

    if (strstr($format, 'Hs')) {
      $str    = sprintf('%02d', abs($this->alt->s));
      $format = str_replace('Hs', $str, $format);
    }

    if (strstr($format, 'Hu')) {
      $str    = sprintf('%02.3f', abs(intval($this->alt->s) - $this->alt->s));
      $str    = str_replace('0.', '', $str);
      $format = str_replace('Hu', $str, $format);
    }

    if (strstr($format, 'H')) {
      $repl   = strstr($format, '+H') ? '+H' : 'H';
      $fmt    = strstr($format, '+H') ? '%+03.5f' : '%03.5f';
      $str    = sprintf($fmt, $this->alt->deg);
      $format = str_replace($repl, $str, $format);
      $format = str_replace("\\$str", 'H', $format);
    }

    // //

    if (strstr($format, 'Ad')) {
      $str    = sprintf('%03d', $this->az->d);
      $format = str_replace('Ad', $str, $format);
    }

    if (strstr($format, 'Am')) {
      $str    = sprintf('%02d', abs($this->az->m));
      $format = str_replace('Am', $str, $format);
    }

    if (strstr($format, 'As')) {
      $str    = sprintf('%02d', abs($this->az->s));
      $format = str_replace('As', $str, $format);
    }

    if (strstr($format, 'Au')) {
      $str    = sprintf('%02.3f', abs(intval($this->az->s) - $this->az->s));
      $str    = str_replace('0.', '', $str);
      $format = str_replace('Au', $str, $format);
    }

    if (strstr($format, 'A')) {
      $str    = sprintf('%09.5F', $this->az->deg);
      $format = str_replace('A', $str, $format);
      $format = str_replace("\\$str", 'A', $format);
    }

    // // //

    if (strstr($format, 'Dau')) {
      $str    = sprintf('%02.3f', $this->dist->au);
      $format = str_replace('Dau', "$str AU", $format);
    }

    if (strstr($format, 'Da')) {
      if ($this->dist->au < 1) {
        $u   = 'km';
        $str = sprintf('%02.3f', $this->dist->km);
      }
      else if ($this->dist->pc > 1) {
        $u   = 'pc';
        $str = sprintf('%02.3f', $this->dist->pc);
      }
      else {
        $u   = 'au';
        $str = sprintf('%02.3f', $this->dist->au);
      }
      $str = number_format($str, 3, '.', ',');

      $format = str_replace('Da', "$str $u", $format);
    }

    // // //

    $format = str_replace("\\", '', $format);
    return $format;
  }

}
