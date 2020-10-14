// @flow
import React from 'react';
import classNames from 'classnames';
import {
  TableHead, TableRow, TableCell, IconButton, Divider,
} from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import Switch from '../../../../shared/components/Switch';

import { FIRST_TABLE_HEAD_ITEM } from './config';

import { getColumnVisibilityValues } from '../utils';

import type { TableHeadRowProps as IProps } from './types';
import type { Counter as CounterType } from '../../../../redux/counterGrid/types';

import { Cell } from '../TableCell/styles';
import styles from './styles';

const TableHeadRow = ({
  counters, actions, classes, handleColumnCheck, handleColumnCheckReverse,
}: IProps): React$Element<any> => (
  <TableHead className={classes.tHead}>
    <TableRow>
      {
        [FIRST_TABLE_HEAD_ITEM, ...counters].map(
          (counter: CounterType, i: number): React$Element<any> => {
            const isSticky = !i && classes.tHeadCellSticky;
            const isLeftAlignment = i || classes.tHeadCellLabelLeft;
            const tableHeadStyle = classNames(classes.tHeadCell, isSticky);
            const tableHeadLabelStyle = classNames(classes.tHeadCellLabel, isLeftAlignment);

            return (
              <TableCell
                key={counter.id}
                align={i ? 'right' : 'left'}
                className={tableHeadStyle}
              >
                {i ? (
                  <Cell>
                    <Switch
                      label={counter.name}
                      inputProps={{ 'aria-label': 'primary checkbox' }}
                      color="primary"
                      onChange={handleColumnCheck(i - 1)}
                      size="large"
                      disableRipple
                      checked={getColumnVisibilityValues(i - 1, actions)}
                    />
                    <IconButton
                      className={classes.icon}
                      onClick={handleColumnCheckReverse(i - 1)}
                      title="Reverse"
                      disableRipple
                    >
                      &#8635;
                    </IconButton>
                  </Cell>
                ) : (
                  <h2 className={tableHeadLabelStyle}>{counter.name}</h2>
                )}
                <Divider />
              </TableCell>
            );
          },
        )
      }
    </TableRow>
  </TableHead>
);

export default withStyles(styles)(TableHeadRow);
