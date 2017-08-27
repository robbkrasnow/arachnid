# -*- coding: UTF-8 -*-

"""
    This script takes an input of a valid mbox file and parses
    the senders, receivers, dates sent, subjects, and body of all
    email messages. It will then create the nodes, links, and messages
    JSON objects to be sent back to PHP and down the SOA in order
    for D3 to visualiz the mailbox.

    @author     Robb Krasnow
    @version    1.0
"""

# Import everything native to Python 2.7
import mailbox
import json
import sys
import re
from collections import OrderedDict, Counter


def parse_mbox(filename):
    """
        This method takes in the file and parses the entire thing
        for all necessary data from each email message. It will
        locate the following:
            - sender
            - receivers
            - date sent
            - subject
            - email body
            - Gmail labels (Future Feature)

        NOTE:
            For a future feature, code has been added to search a Gmail
            mailbox for Gmail labels. The code is commented out now, but
            has been tested and works. It will be added in future releases.

        @param      filename    The hashed filename from the file that resides on the server after upload
        @return     data        The entire set of nodes, links, and messages JSON
    """

    # Open the mbox file
    mbox = mailbox.mbox(filename)

    # Set variables for collecting and maintaining email address and JSON objects
    email_addresses = []
    nodes = []
    links = []
    messages = []
    message_id = 0

    # Lopp through every message in the mbox and grab important information
    for msg in mbox:
        senders_string = msg['from']
        receivers_string = msg['to']
        date_sent = msg['date']
        subject = msg['subject']
        # gmail_labels = msg['x-gmail-labels']  # NOTE: Future feature

        # Must check if the email is a multi-part email to get the entire payload of the message
        # @see http://www.smipple.net/snippet/IanLewis/Multipart%20Mail%20Processing%20in%20Python
        try:
            # Set a default encoding of utf8 for body parts without encoding
            charset = 'utf8'
            content = ''

            # Check if message is multipart. If so, need to walk every subpart of each part
            if msg.is_multipart():
                for part in msg.walk():
                    # Get the character set of the body part or use default utf8
                    charset if part.get_content_charset() is not None else charset

                    # If the MIME type is text/plain, grab all content and encode with utf8
                    # NOTE: Look into multipart messages that have embedded multiparts
                    if part.get_content_type() == 'text/plain' or part.get_content_type() == 'text/html':
                        content += unicode(part.get_payload(decode=True), charset, 'replace').encode('utf8', 'replace')
            else:
               # Get the character set of the body part or use default utf8
                charset if msg.get_content_charset() is not None else charset

                # Pull message body
                content = unicode(msg.get_payload(decode=True), charset, 'replace').encode('utf8', 'replace')
        except:
            error = 'Error parsing email body!'

        """
        #########################################
        #####  EVERYTHING FOR GMAIL LABELS  #####
        #########################################

        if gmail_labels is not None:
            gmail_labels_list_per_email = []

            if '\n' in gmail_labels:
                gmail_labels = gmail_labels.replace('\n', '')

            # If there are double quotes in the string, that means there is a comma in the label
            # We must find all labels inside double quotes before splitting on comma or we will
            # end up with labels being split
            if '"' in gmail_labels:
                # Search for all labels that contain double quotes
                matches = re.findall(r'"([^"]*)"', gmail_labels)

                for match in matches:
                    gmail_labels_list_per_email.append(match)
                    gmail_labels = gmail_labels.replace('"' + match + '"', '')

            # Now we can split on comma since all labels with double quotes have been removed
            # from the string and added to the gmail labels list
            gmail_labels_list_split = gmail_labels.split(',')

            # Add all other labels for a full list of labels
            for label in gmail_labels_list_split:
                gmail_labels_list_per_email.append(label)

            # Filter out any empty list items
            gmail_labels_list_per_email = filter(None, gmail_labels_list_per_email)
        # Send empty list if no gmail labels so it doesn't use the previous message's labels
        else:
            gmail_labels_list_per_email = []
        """

        ####################################
        #####  EVERYTHING FOR SENDERS  #####
        ####################################

        # Check if there are senders, I have seen messages that contain no sender, not sure how
        if senders_string is not None:
            # Extract email address from the sender string
            senders_list_per_email = find_email_addresses(senders_string)

            # Loop through the set of email addresses and add to the final email
            # addresses list for node/link creation
            for sender in senders_list_per_email:
                email_addresses.append(sender)
        # Set empty list if no senders so it doesn't use the previous message's sender
        else:
            senders_list_per_email = []

        ######################################
        #####  EVERYTHING FOR RECEIVERS  #####
        ######################################

        # Check if the receiver's string is empty (could be empty if Notes included)
        if receivers_string is not None:
            # Extract all email addresses from the receivers string
            # inside a list so we can save in the JSON object as an array
            receivers_list_per_email = find_email_addresses(receivers_string)

            # Loop through the set of email addresses and add to the final email
            # addresses list for node/link creation
            for receiver in receivers_list_per_email:
                email_addresses.append(receiver)
        # Set empty list if no receivers so it doesn't use the previous message's receivers
        else:
            receivers_list_per_email = []

        #############################
        #####  CREATE MESSAGES  #####
        #############################

        # Check if there are any senders. There will only be one item in the
        # list because it's unique, we need to grab that element in order to
        # send it to the message JSON
        if len(senders_list_per_email):
            sender = senders_list_per_email[0]

        # Create the message JSON objects and pass in the unique sender, the list of unique
        # receivers per email, email date, email subject, and email content
        message = create_message(message_id, sender, receivers_list_per_email, date_sent, subject, content)
        # message = create_message(message_id, sender, receivers_list_per_email, date_sent, subject, "content")
        # message = create_message(message_id, sender, receivers_list_per_email, date_sent, subject, "content", gmail_labels_list_per_email)    # NOTE: Future feature

        # Add all message JSON objects to a list to be used for complete JSON output with nodes/links
        messages.append(message)

        # Increase the message id counter
        message_id += 1

    # Create the nodes based on all the email addresses stored
    nodes = create_nodes(email_addresses)

    # Create links between senders and receivers
    links = create_links(messages)

    # Produce output needed for d3 (nodes and links required at minimum)
    data = [
        ('nodes', nodes),
        ('links', links),
        ('messages', messages)
    ]

    # Order data and dump it in a nicely formated JSON string
    data = OrderedDict(data)
    data = json.dumps(data, indent=4)

    # Return the entire set of nodes, links, and messages JSON
    return data


# def create_message(message_id, sender, receivers, date_sent, subject, content, gmail_labels):     # NOTE: Future feature
def create_message(message_id, sender, receivers, date_sent, subject, content):
    """
        This method turns all the messages from the file into an ordered
        list.

        @param      message_id  The ID of the message
        @param      sender      The sender of the message
        @param      receivers   The list of receivers from the message
        @param      date_sent   The date the message was sent
        @param      subject     The subject of the message
        @param      content     The body of the email
        @return     message     The message itself turned into a ordered list
        @see                    https://www.python.org/dev/peps/pep-0372/
        @see                    https://docs.python.org/dev/whatsnew/2.7.html#pep-372-adding-an-ordered-dictionary-to-collections
    """

    # In order to preserve order, turn each message into a list of tuples
    message = [
        ('id', message_id),
        ('sender', sender),
        ('receivers', receivers),
        ('date_sent', date_sent),
        ('subject', subject),
        ('content', content)
        # ('x-gmail-labels', gmail_labels)  # NOTE: Future feature
    ]

    # Keep order of all the contents in the message
    message = OrderedDict(message)

    # Return the newly created message as a list
    return message


def create_nodes(addresses):
    """
        This method creates all the nodes based on a list of addresses
        found within the mailbox's "To:" and "From:" headers.

        @param      addresses   A complete listing of all addresses found in the "To:" and "From:" headers
        @return     nodes       A complete new listing of all the newly created nodes
    """

    # Initialize a nodes list
    nodes = []

    # Use Counter to count unique email addresses in the list, sort them and keep order
    address_count = OrderedDict(sorted(Counter(addresses).items()))

    # Create all nodes
    for address, count in address_count.items():
        # Divide each amount of emails into groups to be used for changing
        # node colors and the legend
        if count >= 0 and count <= 100:
            group = 0
        elif count >= 101 and count <= 500:
            group = 1
        elif count >= 501 and count <= 1000:
            group = 2
        elif count >= 1001 and count <= 5000:
            group = 3
        elif count >= 5001 and count <= 10000:
            group = 4
        elif count >= 10001 and count <= 50000:
            group = 5
        elif count >= 50001:
            group = 6

        # Create the node and include email address for ID, number of messages, and group for color coding
        node = [
            ('id', address),
            ('email_count', count),
            ('group', group)
        ]

        # Keep order of data in the node object and add to the nodes list
        node = OrderedDict(node)
        nodes.append(node)

    # Return the newly created list of nodes
    return nodes


def create_links(messages):
    """
        This method creates all the links based on a list of messages
        found within the mailbox.

        @param      messages    A complete listing of all messages found in the mailbox
        @return     links       A complete new listing of all the newly created links
    """

    # Initialize lists for all the links and their IDs
    links = []
    link_ids = []

    # For every message in the list, grab the sender and receivers
    for m, message in enumerate(messages):
        sender = message.values()[1]
        receivers = message.values()[2]

        # Because receivers is a list by itself, we need to enumerate through that as well
        # This will create a link ID for each sender/receiver pair
        for r, receiver in enumerate(receivers):
            link_id = sender + '|' + receiver
            link_ids.append(link_id)

    # Count all the unique link ID pairs, sort them, and keep the ordering
    links_count = OrderedDict(sorted(Counter(link_ids).items()))

    # Create all links
    for id, value in links_count.items():
        id_split = id.split('|')
        source = id_split[0]
        target = id_split[1]

        # Create the link by adding a source (sender) and target (receiver)
        # Also add a value which is the email count for a particular link ID pair
        link = [
            ('source', source),
            ('target', target),
            ('value', value)
        ]

        # Keep ordering of the link then append the link to the links list
        link = OrderedDict(link)
        links.append(link)

    # Return the newly created set of links
    return links


def find_email_addresses(email_string):
    """
        This method is used to find any valid email address contain the
        @ symbol. Once it files the valid addresses, it adds them to a list
        in order to be used for creating nodes.

        @param      addresses   A complete listing of all addresses found in the "To:" and "From:" headers
        @return     nodes       A complete new listing of all the newly created nodes
    """

    if email_string is not None:
        # Create list for final set of email addresses
        email_addresses = []

        # Set regex to search for email addresses
        regex = r'[\w\.-]+@[\w\.-]+'

        # Get all matches of one's email address
        matches = re.findall(regex, email_string)

        # Set all address to lowercase for cases like:
        # - To: "robbkrasnow@gmail.com" <ROBBKRASNOW@GMAIL.COM>
        # - To: "ROBBKRASNOW@GMAIL>COM" <robbkrasnow@gmail.com>
        for match in matches:
            email_addresses.append(match.lower())

        # Set email_addresses to have a unique list of all email addresses
        email_addresses = list(set(email_addresses))

    return email_addresses


if __name__ == '__main__':
    """
        Main method that calls on parse_mbox to do all the work.
        Prints the output in order to send it back to PHP for adding
        it to the DB.
    """

    # begin = datetime.now()    # NOTE: For debugging

    print parse_mbox(sys.argv[1])

    """
    # end = datetime.now()
    # duration = end - begin

    # print "===================================="
    # print "   Begin: " + str(begin)
    # print "     End: " + str(end)
    # print "Duration: " + str(duration)
    # print "===================================="
    """
