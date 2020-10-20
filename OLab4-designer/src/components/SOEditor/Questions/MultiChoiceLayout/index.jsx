// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { TextField, Divider } from '@material-ui/core';
import Switch from '../../../../shared/components/Switch';
import OutlinedSelect from '../../../../shared/components/OutlinedSelect';
import ListWithSearch from '../../../../shared/components/ListWithSearch';
import type { ScopedObjectListItem as ScopedObjectListItemType } from '../../../../redux/scopedObjects/types';

import { LAYOUT_TYPES } from '../config';
import { EDITORS_FIELDS } from '../../config';

import type { IMultiChoiceLayoutProps } from './types';

import styles, { SwitchWrapper, ListWithSearchWrapper } from './styles';
import { FieldLabel } from '../../styles';

const isHideSearch = true;

function MultiChoiceLayout({
  classes,
  feedback,
  history,
  isFieldsDisabled,
  isShowAnswer,
  isShowSubmit,
  layoutType,
  onInputChange,
  onSelectChange,
  onSwitchChange,
  pathName,
  responses,
}: IMultiChoiceLayoutProps) {
  const handleScopedObjectClick = (scopedObject: ScopedObjectListItemType): void => {
    history.push(`${pathName}/${scopedObject.id}`);
  };

  return (
    <>
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
        {EDITORS_FIELDS.RESPONSES}
      </FieldLabel>

      <ListWithSearchWrapper>
        <ListWithSearch
          onItemClick={handleScopedObjectClick}
          list={responses}
          isHideSearch={isHideSearch}
          isWithSpinner={false}
        />
      </ListWithSearchWrapper>
      <Divider />

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
}

/*
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

      <ListWithSearchWrapper>
        <ListWithSearch
          onItemClick={this.handleScopedObjectClick}
          list={responses}
          isHideSearch={isHideSearch}
          isWithSpinner={false}
        />
      </ListWithSearchWrapper>

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

*/

export default withStyles(styles)(MultiChoiceLayout);
