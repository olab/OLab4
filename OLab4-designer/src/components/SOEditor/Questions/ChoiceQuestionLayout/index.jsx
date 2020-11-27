// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { Button, TextField, Divider } from '@material-ui/core';
import { Link } from 'react-router-dom';
import { EDITORS_FIELDS, CHOICE_QUESTION_TYPES } from '../../config';
import { LAYOUT_TYPES } from '../config';
import ListWithSearch from '../../../../shared/components/ListWithSearch';
import OutlinedSelect from '../../../../shared/components/OutlinedSelect';
import styles, { FieldLabel, SwitchWrapper, ListWithSearchWrapper } from '../../styles';
import Switch from '../../../../shared/components/Switch';
import type { IChoiceQuestionLayoutProps } from './types';

function ChoiceQuestionLayout({
  isEditMode,
  onInputChange,
  onLayoutTypeChange,
  onQuestionTypeChange,
  onSwitchChange,
  props,
  state,
}: IChoiceQuestionLayoutProps) {
  const isHideSearch = true;
  const getListPrimaryField = (item) => item.response;
  const getListSecondaryField = () => '';
  const onClicked = (): void => false;
  const { classes } = props;
  const {
    description,
    feedback,
    id,
    isFieldsDisabled,
    name,
    showAnswer,
    showSubmit,
    layoutType,
    responses,
    stem,
    questionType,
  } = state;

  return (
    <>
      {(!isEditMode) && (
        <center>
          Create is required before adding Question Responses
        </center>
      )}
      <FieldLabel>
        {EDITORS_FIELDS.QUESTION_TYPES}
      </FieldLabel>
      <OutlinedSelect
        name="questionType"
        value={CHOICE_QUESTION_TYPES[questionType]}
        values={Object.values(CHOICE_QUESTION_TYPES)}
        onChange={onQuestionTypeChange}
        disabled={isFieldsDisabled}
      />

      <FieldLabel>
        {EDITORS_FIELDS.NAME}
        <TextField
          multiline
          rows="1"
          name="name"
          placeholder={EDITORS_FIELDS.NAME}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={name}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.DESCRIPTION}
        <TextField
          multiline
          rows="1"
          name="description"
          placeholder={EDITORS_FIELDS.DESCRIPTION}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={description}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.STEM}
        <TextField
          multiline
          rows="1"
          name="stem"
          placeholder={EDITORS_FIELDS.STEM}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={stem}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <SwitchWrapper>
        <Switch
          name="showAnswer"
          label={EDITORS_FIELDS.SHOW_ANSWER}
          labelPlacement="start"
          checked={showAnswer}
          onChange={onSwitchChange}
          disabled={isFieldsDisabled}
        />
        <Switch
          name="showSubmit"
          label={EDITORS_FIELDS.SHOW_SUBMIT}
          labelPlacement="start"
          checked={showSubmit}
          onChange={onSwitchChange}
          disabled={isFieldsDisabled}
        />
      </SwitchWrapper>

      <FieldLabel>
        {EDITORS_FIELDS.LAYOUT_TYPE}
      </FieldLabel>
      <OutlinedSelect
        name="layoutType"
        value={LAYOUT_TYPES[layoutType]}
        values={LAYOUT_TYPES}
        onChange={onLayoutTypeChange}
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

      {(isEditMode) && (
        <>
          <FieldLabel>
            {EDITORS_FIELDS.RESPONSES}
            <Button
              size="small"
              fullWidth
              variant="contained"
              color="default"
              className={classes.submit}
              component={Link}
              to={`/scopedObject/questionresponse/${id}`}
              target="_blank"
            >
              Edit
            </Button>
          </FieldLabel>

          <ListWithSearchWrapper>
            <ListWithSearch
              onItemClick={onClicked}
              list={responses}
              isWithSpinner={false}
              isHideSearch={isHideSearch}
              primarytext={getListPrimaryField}
              secondarytext={getListSecondaryField}
              showIcons={false}
            />
          </ListWithSearchWrapper>

          <Divider />
        </>
      )}
    </>
  );
}

export default withStyles(styles)(ChoiceQuestionLayout);
