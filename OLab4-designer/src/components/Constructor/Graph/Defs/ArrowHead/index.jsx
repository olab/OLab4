// @flow
/*
This ArrowHead marker is stored in Defs section.
To use it just set css style `marker-end: url(#${idOfThisMarker});`
Or `xlinkHref` attribute of `use` element(be noted that in this case `shadow-root` will be created).

If size of arrow was not specified or `edgeArrowSize = 0` then arrow will be without arrow head
*/
import React from 'react';

import type { IArrowHeadProps } from './types';
import { getMarkerViewBox, getPathToBeDrawn } from './helpers';
import { MARKER_ID } from './config';

import { ArrowWrapper } from './styles';

export const ArrowHead = ({ edgeArrowSize }: IArrowHeadProps) => {
  if (!edgeArrowSize) {
    return null;
  }

  return (
    <marker
      id={MARKER_ID}
      key={MARKER_ID}
      orient="auto"
      strokeWidth="1px"
      stroke="#fff"
      strokeLinecap="round"
      strokeLinejoin="round"
      refX={edgeArrowSize / 2}
      viewBox={getMarkerViewBox(edgeArrowSize)}
    >
      <ArrowWrapper
        d={getPathToBeDrawn(edgeArrowSize)}
        fill="transparent"
      />
    </marker>
  );
};

export default ArrowHead;
