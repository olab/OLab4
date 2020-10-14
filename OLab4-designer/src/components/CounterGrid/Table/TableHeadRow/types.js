// @flow
import type { Counter, CounterActions } from '../../../../redux/counterGrid/types';

export type TableHeadRowProps = {
  classes: {
    [props: string]: any,
  },
  counters: Array<Counter>,
  actions: Array<CounterActions>,
  handleColumnCheck: Function,
  handleColumnCheckReverse: Function,
};
