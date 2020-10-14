// @flow
import React from 'react';
import classNames from 'classnames';
import { TableCell as TableCellMaterial, Checkbox, TextField } from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import type { TableCellProps as IProps } from './types';

import styles, { Cell } from './styles';

const TableCell = ({
  value,
  checked,
  label,
  classes,
  onCheckboxChange,
  onInputChange,
}: IProps): React$Element<any> => {
  const isFirstCell = value === null && checked === null;
  const isSticky = isFirstCell && classes.cellContainerSticky;
  const tableCellWrapperStyle = classNames(classes.cellContainer, isSticky);

  return (
    <TableCellMaterial
      align="left"
      className={tableCellWrapperStyle}
    >
      {isFirstCell ? (
        <div className={classes.firstColumnContainer}>
          <h2 className={classes.firstColumn} title={label}>{label}</h2>
          <div className={classes.verticalDivider} />
        </div>
      ) : (
        <Cell>
          <Checkbox
            disableRipple
            onChange={onCheckboxChange}
            color="primary"
            checked={checked}
          />
          <TextField
            value={value}
            onChange={onInputChange}
            margin="dense"
            variant="outlined"
            className={classes.textField}
          />
        </Cell>
      )}
    </TableCellMaterial>
  );
};

export default withStyles(styles)(TableCell);
