// @flow
import React, { PureComponent } from 'react';
import {
  Paper, Table, TableHead, TableBody, TableRow,
  TableCell, Divider, Tooltip, TableSortLabel,
} from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import ContentEditable from '../../../shared/components/ContentEditable';

import { sortNodesByField } from '../utils';

import { TABLE_HEAD_CELLS, DEFAULT_SORT_STATUS, ORDER } from './config';

import type { Node as NodeType } from '../types';
import type { NodeGridTableProps as IProps, NodeGridTableState as IState } from './types';

import styles, { TableCellLabel, DefaultSortIcon } from './styles';

export class NodeGridTable extends PureComponent<IProps, IState> {
  constructor(props: IProps) {
    super(props);
    const [DEFAULT_HEAD_LABEL] = Object.keys(TABLE_HEAD_CELLS);

    this.state = {
      sortStatus: DEFAULT_SORT_STATUS,
      headLabelKey: DEFAULT_HEAD_LABEL,
    };
  }

  handleChange = (nodeKey: string, index: number): Function => (html: string): void => {
    const { nodes, onTableChange } = this.props;
    const resultNodes = [
      ...nodes.slice(0, index),
      {
        ...nodes[index],
        [nodeKey]: ['x', 'y'].includes(nodeKey)
          ? Number(html)
          : html,
      },
      ...nodes.slice(index + 1),
    ];

    onTableChange(resultNodes);
  };

  handleSort = (headLabelKey: string): Function => (): void => {
    const { sortStatus: stateSortStatus } = this.state;
    const { nodes, onTableChange } = this.props;
    const sortedNodes = nodes.sort(sortNodesByField(headLabelKey, stateSortStatus[headLabelKey]));

    this.setState(({ sortStatus }): IState => ({
      sortStatus: {
        ...sortStatus,
        [headLabelKey]: sortStatus[headLabelKey] === ORDER.ASC
          ? ORDER.DESC
          : ORDER.ASC,
      },
      headLabelKey,
    }));

    onTableChange(sortedNodes);
  }

  render() {
    const { sortStatus, headLabelKey: stateHeadLabelKey } = this.state;
    const { classes, nodes, onSearchPopupClose } = this.props;

    return (
      <Paper className={classes.paper}>
        <Table>
          <TableHead>
            <TableRow className={classes.tableRow}>
              {Object.keys(TABLE_HEAD_CELLS).map((headLabelKey: string): React$Element<any> => {
                const { isSortable } = TABLE_HEAD_CELLS[headLabelKey];
                const { label } = TABLE_HEAD_CELLS[headLabelKey];
                const onClickHandler = isSortable ? this.handleSort(headLabelKey) : null;
                const tableHeadCellKey = label + headLabelKey;
                const isActive = headLabelKey === stateHeadLabelKey;

                return (
                  <TableCell
                    key={tableHeadCellKey}
                    className={classes.tableHeadCell}
                    onClick={onClickHandler}
                  >
                    <TableCellLabel isSortable={isSortable}>
                      {label}
                      {isSortable && (
                        <Tooltip
                          title="sort"
                          placement="bottom-end"
                          enterDelay={300}
                        >
                          {
                            isActive
                              ? (
                                <TableSortLabel
                                  direction={sortStatus[headLabelKey]}
                                  active
                                />
                              )
                              : <DefaultSortIcon />
                          }
                        </Tooltip>
                      )}
                    </TableCellLabel>
                    <Divider className={classes.horizontalDivider} />
                  </TableCell>
                );
              })}
            </TableRow>
          </TableHead>
          <TableBody>
            {nodes.map((node: NodeType, i: number): React$Element<any> => (
              <TableRow hover role="checkbox" tabIndex={-1} className={classes.tableRow} key={node.id}>
                {Object.keys(TABLE_HEAD_CELLS).map((nodeKey: string): React$Element<any> => {
                  const { isEditable } = TABLE_HEAD_CELLS[nodeKey];
                  const taleBodyCellKey = node.id + nodeKey;

                  return (
                    <TableCell
                      className={classes.tableCell}
                      key={taleBodyCellKey}
                    >
                      {
                        isEditable
                          ? (
                            <ContentEditable
                              html={node[nodeKey]}
                              onFocus={onSearchPopupClose}
                              onChange={this.handleChange(nodeKey, i)}
                            />
                          )
                          : node[nodeKey]
                      }
                    </TableCell>
                  );
                })}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Paper>
    );
  }
}

export default withStyles(styles)(NodeGridTable);
