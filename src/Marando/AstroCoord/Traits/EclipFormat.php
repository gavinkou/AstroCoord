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

trait EclipFormat {

  /**
   * String format for the instance
   * @var string
   */
  protected $format;

  public function format($format) {
    // Store current format
    $this->format = $format;

    if (strstr($format, 'Ld')) {
      $str    = sprintf('%02d', $this->lon->d);
      $format = str_replace('Ld', $str, $format);
    }

    if (strstr($format, 'Lm')) {
      $str    = sprintf('%02d', $this->lon->m);
      $format = str_replace('Lm', $str, $format);
    }

    if (strstr($format, 'Ls')) {
      $str    = sprintf('%02d', $this->lon->s);
      $format = str_replace('Ls', $str, $format);
    }

    if (strstr($format, 'Lu')) {
      $str    = sprintf('%02.3f', abs(intval($this->lon->s) - $this->lon->s));
      $str    = str_replace('0.', '', $str);
      $format = str_replace('Lu', $str, $format);
    }

    if (strstr($format, 'L')) {
      $str    = sprintf('%09.5f', $this->lon->deg);
      $format = str_replace('L', $str, $format);
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

    if (strstr($format, 'Bd')) {
      $repl   = strstr($format, '+Bd') ? '+Bd' : 'Bd';
      $fmt    = strstr($format, '+Bd') ? '%+03d' : '%02d';
      $str    = sprintf($fmt, $this->lat->d);
      $format = str_replace($repl, $str, $format);
    }

    if (strstr($format, 'Bm')) {
      $str    = sprintf('%02d', abs($this->lat->m));
      $format = str_replace('Bm', $str, $format);
    }

    if (strstr($format, 'Bs')) {
      $str    = sprintf('%02d', abs($this->lat->s));
      $format = str_replace('Bs', $str, $format);
    }

    if (strstr($format, 'Bu')) {
      $str    = sprintf('%02.3f', abs(intval($this->lat->s) - $this->lat->s));
      $str    = str_replace('0.', '', $str);
      $format = str_replace('Bu', $str, $format);
    }


    if (strstr($format, 'B')) {
      $repl   = strstr($format, '+B') ? '+B' : 'B';
      $fmt    = strstr($format, '+B') ? '%+09.5f' : '%09.5f';
      $str    = sprintf($fmt, $this->lat->deg);
      $format = str_replace($repl, $str, $format);
      $format = str_replace("\\$str", 'B', $format);
    }

    // // //

    $format = str_replace("\\", '', $format);
    return $format;
  }

}
