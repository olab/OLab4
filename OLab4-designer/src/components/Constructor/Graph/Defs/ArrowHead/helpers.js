// @flow
export const getMarkerViewBox = (edgeArrowSize: number): string => (
  `0 -${edgeArrowSize / 2} ${edgeArrowSize} ${edgeArrowSize}`
);

export const getPathToBeDrawn = (edgeArrowSize: number): string => (
  `M0,-${(edgeArrowSize) / 2}L${edgeArrowSize},0L0,${(edgeArrowSize) / 2} Z`
);
