// @flow
import type { Element } from 'react';

export type State = {
  arrowRef: HTMLSpanElement | null,
  open: boolean,
}

export type Props = {
  classes: {
    arrow: string,
    styleTooltip: string,
    arrowPopper: string,
  },
  children: Element<any>,
  tooltipText: string,
  arrow: boolean,
  isClickable: boolean,
}
