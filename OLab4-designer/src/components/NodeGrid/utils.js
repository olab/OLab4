// @flow
import { isString } from '../../helpers/dataTypes';
import removeHTMLTags from '../../helpers/removeHTMLTags';

import { ORDER } from './Table/config';
import { FIELDS_TO_SEARCH } from './config';

import type { NodeGridState, Node as NodeType } from './types';
import type { Node as FullNode } from '../Constructor/Graph/Node/types';

export const getNodesReduced = (propsNodes: Array<FullNode>): NodeGridState => ({
  nodes: propsNodes.map(({
    id, title, text, x, y,
  }: NodeType): NodeType => ({
    x: parseInt(x, 10),
    y: parseInt(y, 10),
    id,
    title,
    text,
  })),
});

export const withoutTags = (field: string | number): string | number => (
  isString(field)
    ? removeHTMLTags(field)
    : field
);

export const sortNodesByField = (
  field: string,
  status: string,
): Function => (a: NodeType, b: NodeType): number => {
  switch (status) {
    case ORDER.DESC:
      return withoutTags(a[field] > withoutTags(b[field]) ? 1 : -1);
    case ORDER.ASC:
      return withoutTags(a[field] < withoutTags(b[field]) ? 1 : -1);
    default: return 0;
  }
};

export const unEscapeHtml = (data: string): string => (data
  .replace(/&lt;/g, '<')
  .replace(/&gt;/g, '>')
  .replace(/&amp;/g, '&')
  .replace(/&nbsp;/g, ' ')
  .replace(/&quot;/g, '"')
  .replace(/&#039;/g, '\'')
);

export const unEscapeNodes = (nodes: NodeGridState): NodeGridState => (
  nodes.map((elem: NodeType): NodeType => {
    FIELDS_TO_SEARCH.forEach((field: string): void => {
      elem[field] = unEscapeHtml(elem[field]);
    });

    return elem;
  })
);
