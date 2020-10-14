// @flow
import type { Node } from '../types';

export type NodeGridTableProps = {
  classes: {
    [props: string]: any,
  },
  nodes: Array<Node>,
  onTableChange: Function,
  onSearchPopupClose: Function,
};

export type SortStatus = {
  id: string,
  x: string,
  y: string,
  title: string,
};

export type NodeGridTableState = {
  sortStatus: SortStatus,
  headLabelKey: string,
};
