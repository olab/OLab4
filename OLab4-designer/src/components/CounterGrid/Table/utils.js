// @flow
import type { CounterActions } from '../../../redux/counterGrid/types';

export const getAction = (
  nodeId: number,
  counterId: number,
  actions: Array<CounterActions>,
): CounterActions => ({
  ...actions.find(
    (item: CounterGridNode): boolean => item.nodeId === nodeId && item.counterId === counterId,
  ),
});

export const getColumnVisibilityValues = (i: number, actions: Array<CounterGridNode>): boolean => (
  actions.every(item => item[i].isVisible)
);
