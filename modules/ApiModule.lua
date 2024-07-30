local p = {}

local http = require("socket.http")
-- local json = require("json")

function p.fetchData(url)
    local response, status, headers = http.request(url)
    if status == 200 and response then
        -- local data = json.decode(response)
        -- return data
        return response
    else
        return nil
    end
end

function p.renderData(frame)
    local bibleVersion = frame.args.bibleVersion or "NABRE"
    local bibleQuote = frame.args.bibleQuote or "John3:16"
    local url = "https://query.bibleget.io?appid=SeminaVerbi&return=html&query=" .. bibleQuote .. "&version=" .. bibleVersion
    local data = p.fetchData(url)
    if data then
        -- return "Data: " .. data.someField
        return data
    else
        return "Failed to fetch data"
    end
end

return p
