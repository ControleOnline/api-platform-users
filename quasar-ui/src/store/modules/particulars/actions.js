/* eslint-disable */
import { api } from "@controleonline/../../src/boot/api";
import SubmissionError from '../../../error/SubmissionError';
import * as types from './mutation_types';

export const getParticularsTypes = ({ commit }, params = {}) => {
  return api.fetch('/particulars_types', { params })
    
    .then(data => {

      if (data['hydra:member'])
        return data['hydra:member'];

      return [];

    }).catch(e => {
      if (e instanceof SubmissionError) {
        commit(types.SET_VIOLATIONS, e.errors);
        // eslint-disable-next-line
        commit(types.SET_ERROR, e.errors._error);
        return;
      }

      commit(types.SET_ERROR, e.message);
    });
};

export const reset = ({ commit }) => {
  commit(types.RESET);
};
