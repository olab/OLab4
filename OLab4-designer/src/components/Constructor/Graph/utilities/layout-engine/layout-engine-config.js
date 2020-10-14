// @flow
/*
  Accumulates all layout engines.
*/
import None from './none';
import SnapToGrid from './snap-to-grid';
import VerticalTree from './vertical-tree';

export type LayoutEngine = None | SnapToGrid | VerticalTree;

const LayoutEngines = {
  None,
  SnapToGrid,
  VerticalTree,
};

export default LayoutEngines;
