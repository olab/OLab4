// @flow

export type INodeProps = {
  classes: {
    [props: string]: any,
  },
  color: string,
  text: string,
  title: string,
  width: number,
  height: number,
  type: number,
  isEnd: boolean,
  isCollapsed: boolean,
  isSelected: boolean,
  isLocked: boolean,
  isLinked: boolean,
  nodeComponentRef: any,
};
