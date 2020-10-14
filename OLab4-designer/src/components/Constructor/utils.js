// @flow
import type { Node as NodeType } from './Graph/Node/types';
import type { Edge as EdgeType } from './Graph/Edge/types';

import { PAGE_TITLES } from '../config';

export const getFocusedNode = (nodes: Array<NodeType>): NodeType | null => {
  const focusedNode = nodes.find(({ isFocused }) => isFocused);

  if (focusedNode) {
    return focusedNode;
  }

  return null;
};

export const getSelectedEdge = (edges: Array<EdgeType>): EdgeType | null => {
  const selectedLink = edges.find(({ isSelected }) => isSelected);

  if (selectedLink) {
    return selectedLink;
  }

  return null;
};

export const setPageTitle = (mapName: string): void => {
  const newTitle = PAGE_TITLES.DESIGNER(mapName);
  if (newTitle !== document.title) {
    document.title = newTitle;
  }
};
