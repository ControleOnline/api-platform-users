/* eslint-disable */
import * as types from './mutation_types';

export default {
  [types.SET_ERROR]     (state, error) {
    Object.assign(state, { error });
  },

  [types.SET_ISLOADING] (state, isLoading = true) {
    Object.assign(state, { isLoading: isLoading });
  },

  [types.SET_VIOLATIONS](state, violations) {
    Object.assign(state, { violations });
  },

  [types.SET_RETRIEVED](state, retrieved) {
    Object.assign(state, { retrieved });
  },

  [types.SET_ITEMS](state, items) {
    Object.assign(state, { items });
  },

  [types.SET_VIEW](state, view) {
    Object.assign(state, { view });
  },

  [types.SET_TOTALITEMS](state, totalItems) {
    Object.assign(state, { totalItems });
  },

  [types.RESET](state) {
    Object.assign(state, {
      isLoading : false,
      error     : '',
      violations: null,
      retrieved : null,
      totalItems: null,
      items     : [],
    });
  },
};
