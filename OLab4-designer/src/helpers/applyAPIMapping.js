// @flow
import { isBoolean, isNumber } from './dataTypes';
import { QUESTION_TYPES } from '../components/SOEditor/config';


import type { QuestionResponse } from '../redux/questionResponses/types';
import type { MapItem } from '../redux/map/types';
import type { MapDetails } from '../redux/mapDetails/types';
import type { CounterActions } from '../redux/counterGrid/types';
import type { Edge } from '../components/Constructor/Graph/Edge/types';
import type { Node } from '../components/Constructor/Graph/Node/types';
import type { DefaultNode, DefaultEdge } from '../redux/defaults/types';
import type { ScopedObject, ScopedObjectListItem, ScopedObjectBase } from '../redux/scopedObjects/types';

export const edgeToServer = (edgeData: Edge): Edge => ({
  id: edgeData.id,
  text: edgeData.label,
  linkStyleId: edgeData.linkStyle,
  thickness: edgeData.thickness,
  color: edgeData.color,
  lineType: edgeData.variant,
  sourceId: edgeData.source,
  destinationId: edgeData.target,
  hidden: Number(edgeData.isHidden),
  followOnce: Number(edgeData.isFollowOnce),
});

export const edgeFromServer = (edgeData: Edge): Edge => ({
  id: edgeData.id,
  label: edgeData.text || '',
  color: edgeData.color,
  variant: edgeData.lineType,
  linkStyle: edgeData.linkStyleId,
  thickness: edgeData.thickness,
  source: edgeData.sourceId,
  target: edgeData.destinationId,
  isHidden: Boolean(edgeData.hidden),
  isFollowOnce: Boolean(edgeData.followOnce),
  isSelected: false,
});

export const edgeDefaultsFromServer = (edgeDefault: DefaultEdge): DefaultEdge => ({
  label: edgeDefault.text,
  color: edgeDefault.color,
  variant: edgeDefault.lineType,
  linkStyle: edgeDefault.linkStyleId,
  thickness: edgeDefault.thickness,
  isHidden: Boolean(edgeDefault.hidden),
  isFollowOnce: Boolean(edgeDefault.followOnce),
});

export const questionResponseToServer = (
  data: QuestionResponse,
): QuestionResponse => ({
  created_at: data.created_at,
  description: data.description,
  feedback: data.feedback,
  from: data.from,
  id: data.id,
  isCorrect: data.isCorrect,
  name: data.name,
  order: data.order,
  questionId: data.questionId,
  response: data.response,
  score: data.score,
  to: data.to,
  updated_At: data.updated_At,
});

export const questionResponseFromServer = (
  data: QuestionResponse,
): QuestionResponse => ({
  created_at: data.created_at,
  description: data.description,
  feedback: data.feedback,
  from: data.from,
  id: data.id,
  isCorrect: data.isCorrect,
  name: data.name,
  order: data.order,
  questionId: data.questionId,
  response: data.response,
  score: data.score,
  to: data.to,
  updated_At: data.updated_At,
});

export const nodeToServer = (data: Node): Node => ({
  id: data.id,
  mapId: data.mapId,
  title: data.title,
  text: data.text,
  typeId: data.type,
  x: data.x,
  y: data.y,
  height: data.height,
  width: data.width,
  locked: Number(data.isLocked),
  collapsed: Number(data.isCollapsed),
  color: data.color,
  visitOnce: Number(data.isVisitOnce),
  isEnd: Number(data.isEnd),
  linkStyleId: data.linkStyle,
  priorityId: data.priorityId,
  linkTypeId: data.linkType,
  annotation: data.annotation,
  info: data.info,
});

export const nodeFromServer = (data: Node): Node => ({
  id: data.id,
  mapId: data.mapId,
  title: data.title,
  x: data.x,
  y: data.y,
  width: data.width || 0,
  height: data.height || 0,
  color: data.color,
  type: data.typeId,
  text: data.text,
  linkStyle: data.linkStyleId,
  priorityId: data.priorityId,
  linkType: data.linkTypeId,
  isCollapsed: Boolean(data.collapsed),
  isLocked: Boolean(data.locked),
  isVisitOnce: Boolean(data.visitOnce),
  isEnd: Boolean(data.isEnd),
  isSelected: false,
  isFocused: false,
  annotation: data.annotation,
  info: data.info,
});

export const nodeDefaultsFromServer = (nodeDefault: DefaultNode): DefaultNode => ({
  title: nodeDefault.title,
  text: nodeDefault.text,
  x: nodeDefault.x,
  y: nodeDefault.y,
  isLocked: Boolean(nodeDefault.locked),
  isCollapsed: Boolean(nodeDefault.collapsed),
  height: nodeDefault.height,
  width: nodeDefault.width,
  linkStyle: nodeDefault.linkStyleId,
  linkType: nodeDefault.linkTypeId,
  type: nodeDefault.typeId,
  color: nodeDefault.color,
});

export const mapDetailsFromServer = (mapData: MapDetails): MapDetails => ({
  id: mapData.id,
  name: mapData.name,
  notes: mapData.notes,
  author: mapData.author,
  themes: mapData.themes,
  themeId: mapData.themeId,
  keywords: mapData.keywords,
  abstract: mapData.abstract,
  description: mapData.description,
  securityType: mapData.securityType,
  isEnabled: Boolean(mapData.enabled),
  isTemplate: Boolean(mapData.isTemplate),
  isLinkLogicVerified: Boolean(mapData.linkLogicVerified),
  isSendXapiStatements: Boolean(mapData.sendXapiStatements),
  isNodeContentVerified: Boolean(mapData.nodeContentVerified),
  isMediaContentComplete: Boolean(mapData.mediaContentComplete),
  isMediaCopyrightVerified: Boolean(mapData.mediaCopyrightVerified),
  isInstructorGuideComplete: Boolean(mapData.instructorGuideComplete),
});

export const mapDetailsToServer = (mapData: MapDetails): MapDetails => ({
  id: mapData.id,
  name: mapData.name,
  notes: mapData.notes,
  author: mapData.author,
  themeId: mapData.themeId,
  keywords: mapData.keywords,
  abstract: mapData.abstract,
  description: mapData.description,
  securityType: mapData.securityType,
  enabled: Number(mapData.isEnabled),
  isTemplate: Number(mapData.isTemplate),
  linkLogicVerified: Number(mapData.isLinkLogicVerified),
  sendXapiStatements: Number(mapData.isSendXapiStatements),
  nodeContentVerified: Number(mapData.isNodeContentVerified),
  mediaContentComplete: Number(mapData.isMediaContentComplete),
  mediaCopyrightVerified: Number(mapData.isMediaCopyrightVerified),
  instructorGuideComplete: Number(mapData.isInstructorGuideComplete),
});

export const mapFromServer = (mapData: MapItem): MapItem => ({
  nodes: mapData.nodes
    ? mapData.nodes.map(node => nodeFromServer(node))
    : [],
  edges: mapData.links
    ? mapData.links.map(edge => edgeFromServer(edge))
    : [],
});

export const mapFromServerOnCreate = (
  { nodes, edges, ...mapDetails }: { nodes: Node, edges: Edge, mapDetails: MapDetails },
) => ({
  ...mapDetailsFromServer(mapDetails),
  ...mapFromServer({ nodes, edges }),
});

export const templateFromServer = mapFromServer;


export const scopedObjectByTypeFromServer = ({
  url,
  isPrivate,
  showAnswer,
  showSubmit,
  ...restSO
}: ScopedObjectListItem): ScopedObjectListItem => {
  const objectPayload = {
    ...restSO,
    ...(isBoolean(isPrivate) && { isPrivate: Number(isPrivate) }),
    ...(isNumber(showAnswer) && { showAnswer: Number(showAnswer) }),
    ...(isNumber(showSubmit) && { showSubmit: Number(showSubmit) }),
  };

  return objectPayload;
};

export const scopedObjectFromServer = (
  { url, ...restSO }: ScopedObject | ScopedObjectListItem,
): ScopedObject => {
  const objectPayload = {
    ...restSO,
    details: null,
    isShowEyeIcon: Boolean(url),
    isDetailsFetching: false,
  };

  return objectPayload;
};

export const scopedObjectToServer = (SO: ScopedObjectBase): ScopedObjectBase => {
  if (Number(Object.keys(QUESTION_TYPES)[0]) === SO.questionType) {
    const {
      feedback, layoutType, isPrivate, showAnswer, showSubmit, ...restSO
    } = SO;

    return {
      ...restSO,
      ...(!SO.placeholder && { placeholder: 'Default Placeholder Value' }),
    };
  }

  const {
    width, height, placeholder, isPrivate, showAnswer, showSubmit, ...restSO
  } = SO;

  const serverPayload = {
    ...restSO,
    ...(isBoolean(isPrivate) && { isPrivate: Number(isPrivate) }),
    ...(isBoolean(showAnswer) && { showAnswer: Number(showAnswer) }),
    ...(isBoolean(showSubmit) && { showSubmit: Number(showSubmit) }),
  };

  return serverPayload;
};

export const scopedObjectDetailsFromServer = ({
  id,
  name,
  parentId,
  url,
  ...restSODetails
}: ScopedObject): ScopedObject => ({
  id,
  name,
  ...(parentId && { parentId }),
  ...restSODetails,
  ...(url && { isShowEyeIcon: Boolean(url) }),
});

export const counterGridActionsFromServer = (
  { visible, ...restActions }: CounterActions,
): CounterActions => ({
  ...restActions,
  isVisible: Boolean(visible),
});

export const counterGridActionsToServer = (
  { isVisible, ...restActions }: CounterActions,
): CounterActions => ({
  ...restActions,
  visible: Number(isVisible),
});
