// @flow
import React, { Component } from 'react';
import {
  Paper, Table, TableBody, TableRow,
} from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import TableHeadRow from './TableHeadRow';
import TableCell from './TableCell';

import { getAction } from './utils';

import type { CounterGridTableProps as IProps, CounterGridTableState as IState } from './types';
import type {
  Counter as CounterType,
  CounterGridNode as CounterGridNodeType,
} from '../../../redux/counterGrid/types';

import styles from './styles';

class CounterGridTable extends Component<IProps, IState> {
  constructor(props: IProps) {
    super(props);
    const { nodes, counters, actions } = props;

    this.state = {
      countersValues: [
        ...nodes.map((node: CounterGridNodeType): Array<CounterType> => [
          ...counters.map((counter: CounterType): CounterType => (
            getAction(node.id, counter.id, actions)
          )),
        ]),
      ],
    };
  }

  changeMultipleValues = (
    prevState: IState,
    index: number,
    checked: boolean,
  ): IState => {
    const countersValues = prevState.countersValues.map(
      (counters: Array<CounterType>): Array<CounterType> => (
        counters.map(
          (counterAction: CounterType, i: number): CounterType => {
            const isCellChoosen = i === index;
            const isCounterVisible = counterAction.isVisible;
            const reverseVisibleValue = isCellChoosen && { isVisible: !isCounterVisible };
            const multipleVisibleValue = {
              isVisible: isCellChoosen ? checked : isCounterVisible,
            };

            return ({
              ...counterAction,
              ...(checked !== undefined ? multipleVisibleValue : reverseVisibleValue),
            });
          },
        )
      ),
    );

    return { countersValues };
  }

  updateStateAfterCheck = (
    prevState: IState,
    i: number,
    j: number,
    key: string,
    value: string | number,
  ): IState => {
    const countersValues = [...prevState.countersValues];
    countersValues[i][j] = {
      ...countersValues[i][j],
      [key]: value,
    };

    return { countersValues };
  }

  handleColumnCheckReverse = (index: number): Function => (): void => {
    this.setState(prevState => this.changeMultipleValues(prevState, index));
  }

  handleColumnCheck = (index: number): Function => (e: Event): void => {
    const { checked } = (e.target: window.HTMLInputElement);
    this.setState(prevState => this.changeMultipleValues(prevState, index, checked));
  }

  handleInputChange = (i: number, j: number): Function => (e: Event): void => {
    const { value } = (e.target: window.HTMLInputElement);
    this.setState(prevState => this.updateStateAfterCheck(prevState, i, j, 'expression', value));
  }

  handleCheckboxChange = (i: number, j: number): Function => (e: Event): void => {
    const { checked } = (e.target: window.HTMLInputElement);
    this.setState(prevState => this.updateStateAfterCheck(prevState, i, j, 'isVisible', checked));
  }

  render() {
    const { countersValues } = this.state;
    const { nodes, counters, classes } = this.props;

    return (
      <Paper className={classes.paper}>
        <Table>
          <TableHeadRow
            counters={counters}
            actions={countersValues}
            handleColumnCheck={this.handleColumnCheck}
            handleColumnCheckReverse={this.handleColumnCheckReverse}
          />
          <TableBody>
            {nodes.map((node: CounterGridNodeType, i: number): React$Element<any> => {
              const headerCellLabel = `[${node.id}] ${node.title}`;

              return (
                <TableRow hover role="checkbox" tabIndex={-1} key={node.id} className={classes.tableRow}>
                  {[node, ...counters].map((counter: CounterType, j: number) => {
                    const value = j ? countersValues[i][j - 1].expression : null;
                    const checked = j ? Boolean(countersValues[i][j - 1].isVisible) : null;

                    return (
                      <TableCell
                        key={counter.id}
                        align="left"
                        label={headerCellLabel}
                        value={value}
                        checked={checked}
                        onCheckboxChange={this.handleCheckboxChange(i, j - 1)}
                        onInputChange={this.handleInputChange(i, j - 1)}
                      />
                    );
                  })}
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      </Paper>
    );
  }
}

export default withStyles(styles)(CounterGridTable);
