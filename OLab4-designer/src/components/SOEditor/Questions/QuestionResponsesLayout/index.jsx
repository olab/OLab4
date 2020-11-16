// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { Button, TextField, Divider } from '@material-ui/core';
import { Link } from 'react-router-dom';

import { EDITORS_FIELDS } from '../../config';
import { LAYOUT_TYPES } from '../config';
import ListWithSearch from '../../../../shared/components/ListWithSearch';
import OutlinedSelect from '../../../../shared/components/OutlinedSelect';
import styles, { FieldLabel, SwitchWrapper, ListWithSearchWrapper } from '../../styles';
import Switch from '../../../../shared/components/Switch';
import type { IQuestionResponsesLayoutProps } from './types';
import type { ScopedObjectListItem as ScopedObjectListItemType } from '../../../../redux/scopedObjects/types';

const isHideSearch = true;

function QuestionResponsesLayout({
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
}: IQuestionResponsesLayoutProps) {
  const handleScopedObjectClick = (scopedObject: ScopedObjectListItemType): void => {
    window.open(`/scopedObject/questionresponse/${scopedObject.questionId}`);
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

      <Button
        size="small"
        variant="outlined"
        color="primary"
        className={classes.button}
        component={Link}
        to="/scopedObject/questionresponse/3535"
        target="_blank"
      >
        Edit
      </Button>

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

export default withStyles(styles)(QuestionResponsesLayout);
