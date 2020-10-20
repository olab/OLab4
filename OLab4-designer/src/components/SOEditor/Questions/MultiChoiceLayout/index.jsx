// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import {
  List, ListItem, ListItemText, TextField, Typography,
} from '@material-ui/core';
import Switch from '../../../../shared/components/Switch';
import OutlinedSelect from '../../../../shared/components/OutlinedSelect';

import { LAYOUT_TYPES } from '../config';
import { EDITORS_FIELDS } from '../../config';

import type { IMultiChoiceLayoutProps } from './types';

import styles, { SwitchWrapper } from './styles';
import { FieldLabel } from '../../styles';

const listStyle = {
  borderStyle: 'solid',
  borderWidth: '1px',
  borderRadius: '4px',
  borderColor: 'rgba(0, 0, 0, 0.23)',
};

const MultiChoiceLayout = ({
  classes,
  feedback,
  isFieldsDisabled,
  isShowAnswer,
  isShowSubmit,
  layoutType,
  onInputChange,
  onSelectChange,
  onSwitchChange,
  responses,
}: IMultiChoiceLayoutProps) => (
    <>
      <FieldLabel>
        {EDITORS_FIELDS.RESPONSES}
      </FieldLabel>

      <List
        disablePadding
        style={listStyle}
      >
        {responses.map((listItem) => (
          <ListItem
            key={listItem.id}
          >
            <ListItemText
              primary={listItem.name}
            />
          </ListItem>
        ))}

        {!responses.length && (
          <ListItem classes={{ root: classes.listItem }}>
            <Typography align="right" variant="caption">
              Empty list...
            </Typography>
          </ListItem>
        )}
      </List>

      <FieldLabel>
        {EDITORS_FIELDS.LAYOUT_TYPE}
      </FieldLabel>
      <OutlinedSelect
        name="layoutType"
        value={LAYOUT_TYPES[layoutType]}
        values={LAYOUT_TYPES}
        onChange={onSelectChange}
        disabled={isFieldsDisabled}
      />
      <FieldLabel>
        {EDITORS_FIELDS.FEEDBACK}
        <TextField
          multiline
          rows="3"
          name="feedback"
          placeholder={EDITORS_FIELDS.FEEDBACK}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={feedback}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>
      <SwitchWrapper>
        <Switch
          name="isShowAnswer"
          label={EDITORS_FIELDS.SHOW_ANSWER}
          labelPlacement="start"
          checked={isShowAnswer}
          onChange={onSwitchChange}
          disabled={isFieldsDisabled}
        />
        <Switch
          name="isShowSubmit"
          label={EDITORS_FIELDS.SHOW_SUBMIT}
          labelPlacement="start"
          checked={isShowSubmit}
          onChange={onSwitchChange}
          disabled={isFieldsDisabled}
        />
      </SwitchWrapper>
    </>
);

export default withStyles(styles)(MultiChoiceLayout);
