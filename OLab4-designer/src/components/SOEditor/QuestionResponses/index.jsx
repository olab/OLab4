// @flow
import React from 'react';
import { ToggleButtonGroup, ToggleButton } from '@material-ui/lab';
import { Delete as DeleteIcon } from '@material-ui/icons';
import { IconButton } from '@material-ui/core';
import log from 'loglevel';
import { EDITORS_FIELDS, QUESTION_TYPES, CORRECTNESS_TYPES } from '../config';
import { FieldLabel } from '../styles';
import { isPositiveInteger } from '../../../helpers/dataTypes';
import { OtherContent, FullContainerWidth } from './styles';
import { SCOPED_OBJECTS } from '../../config';
import { withQuestionResponseRedux } from './index.service';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import ScopedObjectService from '../index.service';
import TitledButton from '../../../shared/components/TitledButton';
import type { IQuestionResponseProps } from './types';

class QuestionResponses extends ScopedObjectService {
  constructor(props: IQuestionResponseProps) {
    super(props, SCOPED_OBJECTS.QUESTION.name);
    this.state = {
      addIndex: -1,
      isFieldsDisabled: false,
      isDetailsFetching: true,
      questionId: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      responses: [],
    };

    // log.setDefaultLevel('trace');
    this.setPageTitle();
  }

  buildId = (name: String, index: Number): void => `${name.toLowerCase()}-${index}`;

  onToggleButtonChange = (e: Event): void => {
    const { value: index } = e.target.parentNode.parentNode.parentNode.parentNode.children[0];
    const { innerHTML: stringValue } = e.target;
    const { state } = this;
    const responses = [...state.responses];

    const [value] = Object.keys(CORRECTNESS_TYPES).filter(
      (key) => CORRECTNESS_TYPES[key] === stringValue,
    );

    if (typeof value !== 'undefined') {
      if (value.length > 0) {
        const fieldName = 'isCorrect';
        responses[Number(index)][fieldName] = Number(value);
        if (responses[index].dbOperation !== 'create') {
          responses[index].dbOperation = 'edit';
        }
        log.debug(`${fieldName}[${index}] = ${value}`);
      }
    }

    this.setState({ responses });
  }

  onIntegerTextChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const [fieldName, index] = name.split('-');
    const { state } = this;
    const responses = [...state.responses];

    if (isPositiveInteger(value)) {
      const int = parseInt(value, 10);
      responses[index][fieldName] = int;
      if (responses[index].dbOperation !== 'create') {
        responses[index].dbOperation = 'edit';
      }
      log.debug(`${fieldName}[${index}] = ${value}`);
    }

    this.setState({ responses });
  }

  onTextChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const [fieldName, index] = name.split('-');
    const { state } = this;
    const responses = [...state.responses];

    responses[index][fieldName] = value;
    if (responses[index].dbOperation !== 'create') {
      responses[index].dbOperation = 'edit';
    }
    log.debug(`${fieldName}[${index}] = ${value}`);
    this.setState({ responses });
  }

  onItemDelete = (id): void => {
    log.debug(`deleting id = ${id}`);
    const {
      state,
    } = this;
    const responses = [...state.responses];
    responses.forEach((value) => {
      if (value.id === id) {
        value.dbOperation = 'delete';
      }
    });
    this.setState({ responses });
  }

  onClickCreate = (questionId) => {
    log.debug('creating record');
    let { addIndex } = this.state;
    const {
      state,
    } = this;
    const responses = [...state.responses];
    const newResponse = {
      id: addIndex,
      name: '',
      dbOperation: 'create',
      description: '',
      response: '',
      score: 0,
      order: 0,
      questionId,
      isCorrect: 2,
    };

    addIndex -= 1;
    responses.push(newResponse);
    this.setState({ responses });
  };

  onClickRevert = () => {
    const { ACTION_SCOPED_OBJECT_DETAILS_REQUESTED } = this.props;
    const { id } = this.state;
    ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(id);
  }

  isDataChanged = (): void => {
    const {
      ...scopedObjectData
    } = this.state;
    const { responses } = scopedObjectData;
    const changed = responses.filter((value) => value.dbOperation === 'delete' || value.dbOperation === 'edit' || value.dbOperation === 'create');
    return changed.length > 0;
  }

  onClickUpdate = (): void => {
    const {
      isFieldsDisabled,
      isShowModal,
      ...scopedObjectData
    } = this.state;
    const {
      ACTION_SCOPED_OBJECT_CREATE_REQUESTED,
      ACTION_SCOPED_OBJECT_UPDATE_REQUESTED,
      ACTION_SCOPED_OBJECT_DELETE_REQUESTED,
    } = this.props;

    let { responses } = scopedObjectData;
    responses.forEach(response => {
      switch (response.dbOperation) {
        case 'edit':
          ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(response);
          break;
        case 'create':
          ACTION_SCOPED_OBJECT_CREATE_REQUESTED(response);
          break;
        case 'delete':
          ACTION_SCOPED_OBJECT_DELETE_REQUESTED(response.id);
          break;
        default:
          break;
      }
    });

    // remove any deleted responses
    responses = responses.filter((value) => value.dbOperation !== 'delete');
    // reset and db operation flags
    responses.forEach(response => {
      response.dbOperation = null;
    });

    this.setState({ responses });
  }

  render() {
    const {
      isDetailsFetching,
      isFieldsDisabled,
      id,
      responses,
    } = this.state;
    const { classes } = this.props;

    if (isDetailsFetching) {
      return <CircularSpinnerWithText text="Data is being fetched..." large centered />;
    }

    return (
      <>
        <EditorWrapper
          hasBackButton={false}
          isDisabled={isFieldsDisabled}
          isEditMode={this.isEditMode}
          onRevert={this.onClickRevert}
          onSubmit={() => this.onClickUpdate()}
          dataChanged={() => this.isDataChanged()}
          scopedObject={this.scopedObjectType}
        >
          {responses.map((item, i) => item.dbOperation !== 'delete' && (
            <OtherContent>
              <input value={i} type="hidden" />
              <FullContainerWidth>
                <FieldLabel>
                  {i === 0 && (
                    <>
                      {EDITORS_FIELDS.RESPONSE}
                    </>
                  )}
                  <OutlinedInput
                    name={this.buildId('response', i)}
                    placeholder={EDITORS_FIELDS.RESPONSE}
                    value={item.response}
                    onChange={this.onTextChange}
                    disabled={isFieldsDisabled}
                    fullWidth
                  />
                </FieldLabel>
              </FullContainerWidth>
              <FullContainerWidth>
                <FieldLabel>
                  {i === 0 && (
                    <>
                      {EDITORS_FIELDS.FEEDBACK}
                    </>
                  )}
                  <OutlinedInput
                    name={this.buildId('feedback', i)}
                    placeholder={EDITORS_FIELDS.FEEDBACK}
                    value={item.feedback}
                    onChange={this.onTextChange}
                    disabled={isFieldsDisabled}
                    fullWidth
                  />
                </FieldLabel>
              </FullContainerWidth>
              <FullContainerWidth>
                <FieldLabel>
                  {i === 0 && (
                    <>
                      {EDITORS_FIELDS.SCORE}
                    </>
                  )}
                  <OutlinedInput
                    name={this.buildId('score', i)}
                    placeholder={EDITORS_FIELDS.SCORE}
                    value={item.score}
                    onChange={this.onIntegerTextChange}
                    disabled={isFieldsDisabled}
                    fullWidth
                  />
                </FieldLabel>
              </FullContainerWidth>
              <FullContainerWidth>
                <FieldLabel>
                  {i === 0 && (
                    <>
                      {EDITORS_FIELDS.ORDER}
                    </>
                  )}
                  <OutlinedInput
                    name={this.buildId('order', i)}
                    placeholder={EDITORS_FIELDS.ORDER}
                    value={item.order}
                    onChange={this.onIntegerTextChange}
                    disabled={isFieldsDisabled}
                    fullWidth
                  />
                </FieldLabel>
              </FullContainerWidth>
              <FullContainerWidth>
                <FieldLabel>
                  {i === 0 && (
                    <>
                      {EDITORS_FIELDS.IS_CORRECT}
                    </>
                  )}
                </FieldLabel>
                <ToggleButtonGroup
                  size="small"
                  name="isCorrect"
                  orientation="horizontal"
                  value={Number(item.isCorrect)}
                  exclusive
                  onChange={this.onToggleButtonChange}
                >
                  <ToggleButton
                    name={this.buildId('correct', i)}
                    classes={{ root: this.props.classes.toggleButton }}
                    value={1}
                    aria-label="module"
                  >
                    {CORRECTNESS_TYPES[1]}
                  </ToggleButton>
                  <ToggleButton
                    name={this.buildId('neutral', i)}
                    classes={{ root: this.props.classes.toggleButton }}
                    value={2}
                    aria-label="quilt"
                  >
                    {CORRECTNESS_TYPES[2]}
                  </ToggleButton>
                  <ToggleButton
                    name={this.buildId('incorrect', i)}
                    classes={{ root: this.props.classes.toggleButton }}
                    value={0}
                    aria-label="list"
                  >
                    {CORRECTNESS_TYPES[0]}
                  </ToggleButton>
                </ToggleButtonGroup>
              </FullContainerWidth>
              <FullContainerWidth>
                <FieldLabel>
                  {i === 0 && (
                    <>
                      <br />
                    </>
                  )}
                  <IconButton
                    size="small"
                    title={`Delete ${item.response}`}
                    aria-label="Delete Scoped Object"
                    onClick={() => this.onItemDelete(item.id)}
                    classes={{ root: classes.deleteIcon }}
                  >
                    <DeleteIcon />
                  </IconButton>
                </FieldLabel>
              </FullContainerWidth>
            </OtherContent>
          ))}
          <FieldLabel>
            <TitledButton
              title="Add new record"
              label="Add"
              type="submit"
              className={classes.submit}
              onClick={() => this.onClickCreate(id)}
            />
          </FieldLabel>
        </EditorWrapper>
      </>
    );
  }
}

export default withQuestionResponseRedux(
  QuestionResponses,
  SCOPED_OBJECTS.QUESTION.name,
);
