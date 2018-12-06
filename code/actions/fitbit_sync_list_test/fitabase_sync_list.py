#!/var/www/html/applications/fitbit/env/bin/python
# -*- coding: utf-8 -*-
"""
An alternative extractor of the same information as fitbit_sync_list, but using 
pandas / pycap for data extraction and jinja for templating.

Potential improvements:

    - more flags
    - better problem highlighting / better visualization
    - accept action_params, e.g. to filter out test cases / filter out cases 
      with no flags
    - make id_redcap into a Redcap link - makes most sense
    - make it faster - setting dtypes and datetime formats, avoiding later 
      concat, ...
"""

import argparse
from collections import OrderedDict
import os
import json
# import line_profiler
import logging as log
import pandas as pd
import redcap as rc
from render_jinja import ApprisePandasToEmail
import sys

CURRENT_DIR = os.path.join(os.path.dirname(os.path.realpath(__file__)))
log.basicConfig(
        filename=os.path.join(CURRENT_DIR, os.path.basename(__file__) + ".log"), 
        format="%(asctime)s  %(levelname)10s  %(message)s",
        level=log.INFO)

def linker(x, template, title):
    """
    Helper function for HTML links. `template` will have a printf-style marker in 
    it that `x` will fill at URL; `title` is always the visible portion of the 
    link.
    """
    link = '<a href="' + (template % x) + '">' + ("%s</a>" % title)
    return link

def get_day_delta(data, colnames=None):
    """
    For all columns of type 'timedelta64', create an unrounded float-value 
    representation and name it ${COLNAME}_days. 
    
    (Note that this is different from data['colname'].dt.days - that value just 
    gives the day *component* of the full timedelta.
    """
    # Result should be joinable to main table
    if colnames is not None:
        subset = subset.loc[:, colnames]
    subset = data.select_dtypes(include='timedelta64')
    subset = subset.apply(lambda x: x / pd.Timedelta(days=1))
    subset = subset.rename(columns=lambda x: x + '_days')
    return subset

            
def parse_json_arg():
    """
    This script isn't getting standard UNIX-style CLI parameters; rather, it's 
    getting a single JSON string that needs to be decoded.
    """
    parser = argparse.ArgumentParser(
            description=__doc__)
    parser.add_argument('json', 
            type=json.loads,
            help=('A stringified JSON from the report invocation; should '
                'always have `id`, `user`, `event`, `action`, `event_param`, '
                '`action_param`, and `sites`.'))
    parsed = parser.parse_args()

    return parsed.json


def validate_params(params):
    """
    TODO: Validate the parameters we care about - `sites` and `action_param`.
    """
    required_cols = ['id', 'user', 'event', 'action', 'event_param', 'action_param', 'sites']
    if not set(parsed.keys()).issuperset(required_cols):
        raise KeyError('Parameters must have all keys: %s' % required_cols)


# @profile
def load_fitbit_data(rc_api):
    data = rc_api.export_records(format='df', forms=['consentfitbit'],
            df_kwargs={
                'parse_dates': ['fitc_device_dte', 'fitc_last_sync_date', 
                    'fitc_last_dte_ra_contact', 'fitc_last_dte_daic_contact',
                    #'fitc_last_dte_contact',  # actually contains a string
                    ], 
                'index_col': [rc_api.def_field, 'redcap_event_name']})
    return data

if __name__ == "__main__":
    PII_LINK = 'https://abcdcontact.me:8888/fitbit.php?pGUID=%s'
    REDCAP_LINK = 'https://abcd-rc.ucsd.edu/redcap/redcap_v8.7.0/DataEntry/record_home.php?pid=12&arm=1&id=%s'
    REDCAP_URL = 'https://abcd-rc.ucsd.edu/redcap/api/'

    with open('/var/www/html/code/php/tokens.json', 'r') as data_file:
        redcap_tokens = json.load(data_file)
        redcap_tokens = pd.DataFrame.from_dict(redcap_tokens, orient='index', columns=['token'])

    params = parse_json_arg()

    site_dataframes = []
    for site in params['sites']:
        # 1. Download fitc_* from Redcap
        log.info('%s: Started processing', site)
        try:
            rc_token = redcap_tokens.loc[site, 'token']
        except KeyError:
            log.warn('%s: No Redcap token, skipping', site)
            continue
        rc_api = rc.Project(REDCAP_URL, rc_token)
        try:
            data = load_fitbit_data(rc_api)
        except pd.errors.EmptyDataError as e:
            log.warn('%s: No records retrieved from Redcap?', site)
            continue

        data['site'] = site

        # Create columns for interesting timedeltas
        data['start_delta'] = pd.to_datetime('today') - data['fitc_device_dte']
        data['sync_delta'] = pd.to_datetime('today') - data['fitc_last_sync_date']
        data['human_contact_delta'] = pd.to_datetime('today') - data['fitc_last_dte_ra_contact']
        data['auto_contact_delta'] = pd.to_datetime('today') - data['fitc_last_dte_daic_contact']

        # Compute all float-value day deltas into separate columns
        data = data.join(get_day_delta(data))

        # Only include active participants
        data = data.loc[(data['start_delta_days'] >= 0) & (data['start_delta_days'] <= 23)]

        # Early selection to keep memory usage reasonable (premature 
        # optimization - burn it if it stands in the way)
        selected_cols = ['site', 'start_delta_days', 'sync_delta_days', 
                'human_contact_delta_days', 'auto_contact_delta_days',
                'fitc_fitabase_exists', 'fitc_last_battery_level', 
                'fitc_last_dte_contact', 'fitc_last_dte_ra_contact',  
                'fitc_last_dte_daic_contact', 'fitc_last_status_contact', 
                'fitc_number_devices']

        data = data.loc[:, selected_cols]
        log.info('%s: Finished processing and appended %d records', site, data.shape[0])
        site_dataframes.append(data)


    # If an error befell every site:
    if len(site_dataframes) == 0:
        log.warn("No eligible devices in call: %s", sys.argv)
        sys.exit(1)

    # If not, proceed to do the thing:
    all_data = pd.concat(site_dataframes)
    all_data.reset_index(inplace=True)
    # Create convenience hyperlinks
    all_data['redcap_link'] = all_data['id_redcap'].apply(linker, 
            template=REDCAP_LINK, title='Redcap')
    all_data['pii_link'] = all_data['id_redcap'].apply(linker, 
            template=PII_LINK, title='PII')

    # Each condition evaluates to True at indices that have a problem. In 
    # addition to flagging those individual problems, we also count the 
    # number of problems in order to present most problematic cases first.
    #
    # Change: we should probably keep the flags in the DataFrame. That way, 
    # we could style more easily based on adjacent columns, maybe? Or at 
    # least get indexing that's consistent with the resort.
    # 
    # TODO flag: more than a day between flag and contact (possible when we 
    # have the separate timestamps)

    flags = {}
    flags['no_devices'] = (all_data['fitc_number_devices'] == 0) | pd.isnull(all_data['fitc_number_devices'])
    flags['no_battery'] = all_data['fitc_last_battery_level'] == 'EMPTY'
    flags['low_battery'] = (all_data['fitc_last_battery_level'] == 'LOW') & (all_data['sync_delta_days'] > 0.5)
    flags['sync_none'] = pd.isnull(all_data['sync_delta_days'])
    flags['sync_late'] = all_data['sync_delta_days'] >= 3
    flags['sync_overdue'] = all_data['sync_delta_days'] >= 5

    all_data.loc[:, 'problems'] = 0
    for flag_name, flagged_idx in flags.items():
        all_data.loc[flagged_idx, 'problems'] = all_data.loc[flagged_idx, 'problems'] + 1

    all_data.sort_values(by=['problems', 'sync_delta_days'], ascending=False, inplace=True)

    # Silly thing: replace problem count with checkmarks/x-marks
    # (This is apparently a good way to stump both Python and cron, so 
    # maybe later)
    # def replace_problems(x):
    #     if x == 0:
    #         return u"✔"
    #     else:
    #         return u"✘" * x
    # all_data.loc[:, 'problems'] = (all_data.loc[:, 'problems']
    #         .apply(replace_problems)
    #         .astype('unicode'))

    renames = OrderedDict([
        ('id_redcap', 'pGUID'),
        ('redcap_link', 'Redcap'),
        ('pii_link', 'PII'),
        ('redcap_event_name', 'Visit'),
        ('site', 'Site'),
        ('fitc_last_battery_level', 'Battery level at last sync'),
        ('start_delta_days', 'Day 0..23'),
        ('sync_delta_days', 'Days since last sync'),
        # FIXME: Not rolled out yet
        ('human_contact_delta_days', 'Days since human contact'),
        # ('fitc_last_dte_ra_contact', 'Human contact date'),
        ('auto_contact_delta_days', 'Days since automated contact'),
        # ('fitc_last_dte_daic_contact', 'Automated contact date'),
        ('fitc_number_devices', 'Reachable e-mails/phones'),
        ('problems', 'Problem count'),
        ]) 

    all_data = all_data.loc[:, renames.keys()]

    # There has to be a way to do this generally?
    if len(all_data['redcap_event_name'].unique()) == 1:
        all_data.drop(columns=['redcap_event_name'], inplace=True)
    if len(all_data['site'].unique()) == 1:
        all_data.drop(columns=['site'], inplace=True)
    all_data['fitc_last_battery_level'] = all_data['fitc_last_battery_level'].str.capitalize() 
    all_data['fitc_number_devices'] = all_data['fitc_number_devices'].fillna(0)

    all_data.rename(columns=renames, inplace=True)

    def color_0_red_bold(data):
        target_attr = 'color: red; font-weight: bold'
        idx = data == 0
        return [target_attr if v else '' for v in idx]

    all_data.set_index(['pGUID'], inplace=True)
    # NOTE: This is somewhat crude. The "correct" way would be to apply 
    # styles at the same time as we apply flags - but then we couldn't 
    # resort. Maybe save the flags in the DataFrame?
    all_data_styled = (all_data.style
            .format({'Day 0..23': '{:.2f}', 'Days since last sync': '{:+.2f}'})
            .set_precision(3)
            .where(lambda x: pd.isnull(x) | (x in ['Low', 'Empty']), 'color: red', 
                '', subset=['Battery level at last sync'])
            .where(lambda x: pd.isnull(x) | (x >= 4), 'color: red', 
                '', subset=pd.IndexSlice[:, 'Days since last sync'])
            .apply(color_0_red_bold, axis=0, subset=['Reachable e-mails/phones'])
            # .bar(subset=['Day 0..23'], color='#d65f5f')
            # .background_gradient(cmap='inferno', low=0, high=40, 
            # subset=['Days since last sync'])
            )
    print(ApprisePandasToEmail(all_data_styled.render())
            .render_email({'title': 'Fitbit sync + battery status'}))

    log.info("Concluded execution with call: %s", sys.argv)
